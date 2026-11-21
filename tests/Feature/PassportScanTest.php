<?php

namespace Tests\Feature;

use App\Http\Controllers\Customer\PassportScanController;
use App\Models\Customer;
use App\Models\User;
use App\Services\GeminiPassportExtractor;
use App\Services\PassportExtraction;
use App\Services\PassportExtractionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PassportScanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.gemini.key' => 'test-gemini-key', 'services.gemini.model' => 'gemini-2.5-flash']);
    }

    public function test_gemini_extraction_uses_structured_json_and_normalizes_passenger_fields(): void
    {
        $imagePath = $this->createTestImage();
        $extracted = [
            'fields' => [
                'first_name' => 'JANE',
                'last_name' => 'DOE',
                'date_of_birth' => '1990-03-02',
                'gender' => 'F',
                'nationality' => 'USA',
                'passport_number' => 'A 1234-5678',
                'passport_country' => 'USA',
                'passport_expiry' => '2030-01-01',
            ],
            'confidence' => array_fill_keys([
                'first_name', 'last_name', 'date_of_birth', 'gender', 'nationality',
                'passport_number', 'passport_country', 'passport_expiry',
            ], 0.92),
            'mrz_lines' => null,
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode($extracted, JSON_THROW_ON_ERROR)]]]]],
        ])]);

        try {
            $result = (new GeminiPassportExtractor)->extract($imagePath, 42);

            $this->assertTrue($result['available']);
            $this->assertSame([
                'first_name' => 'JANE',
                'last_name' => 'DOE',
                'date_of_birth' => '1990-03-02',
                'gender' => 'female',
                'nationality' => 'American',
                'passport_number' => 'A12345678',
                'passport_country' => 'United States',
                'passport_expiry' => '2030-01-01',
            ], $result['fields']);
            $this->assertSame([], $result['warnings']);

            Http::assertSent(function (ClientRequest $request) use ($imagePath): bool {
                $parts = $request->data()['contents'][0]['parts'];

                return str_starts_with($request->url(), 'https://generativelanguage.googleapis.com/')
                    && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                    && $request->data()['generationConfig']['responseMimeType'] === 'application/json'
                    && $parts[1]['inline_data']['data'] === base64_encode(file_get_contents($imagePath));
            });
        } finally {
            @unlink($imagePath);
        }
    }

    public function test_unreadable_or_ambiguous_fields_are_returned_as_null(): void
    {
        $imagePath = $this->createTestImage();
        $fields = [
            'first_name' => 'A<script>',
            'last_name' => 'Example',
            'date_of_birth' => '2038-02-31',
            'gender' => 'X',
            'nationality' => 'XYZ',
            'passport_number' => 'not a number!',
            'passport_country' => 'Jordan',
            'passport_expiry' => '2030-01-01',
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'fields' => $fields,
                'confidence' => array_fill_keys(array_keys($fields), 0.99),
                'mrz_lines' => null,
            ], JSON_THROW_ON_ERROR)]]]]],
        ])]);

        try {
            $result = (new GeminiPassportExtractor)->extract($imagePath, 42);

            $this->assertNull($result['fields']['first_name']);
            $this->assertNull($result['fields']['date_of_birth']);
            $this->assertNull($result['fields']['gender']);
            $this->assertSame('XYZ', $result['fields']['nationality']);
            $this->assertNull($result['fields']['passport_number']);
            $this->assertSame('Jordan', $result['fields']['passport_country']);
            $this->assertSame(0.0, $result['confidence']['gender']);
        } finally {
            @unlink($imagePath);
        }
    }

    public function test_gemini_malformed_response_is_rejected_without_exposing_provider_content(): void
    {
        $imagePath = $this->createTestImage();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'private provider output']]]]],
        ])]);

        try {
            $this->expectException(PassportExtractionException::class);
            $this->expectExceptionMessage('could not read enough passport details');
            (new GeminiPassportExtractor)->extract($imagePath, 42);
        } finally {
            @unlink($imagePath);
        }
    }

    public function test_missing_api_key_fails_closed_without_sending_the_image(): void
    {
        $imagePath = $this->createTestImage();
        config(['services.gemini.key' => null]);

        try {
            try {
                (new GeminiPassportExtractor)->extract($imagePath, 42);
                $this->fail('Expected extraction to be unavailable without a key.');
            } catch (PassportExtractionException $exception) {
                $this->assertSame(PassportExtractionException::UNAVAILABLE, $exception->reason);
            }

            Http::assertNothingSent();
        } finally {
            @unlink($imagePath);
        }
    }

    public function test_scan_controller_requires_consent_and_deletes_its_temporary_copy(): void
    {
        $sourcePath = $this->createTestImage();
        $customer = new Customer;
        $customer->setAttribute('id', 73);
        $user = new User;
        $user->setRelation('customer', $customer);
        $extractor = Mockery::mock(PassportExtraction::class);
        $extractor->shouldReceive('extract')->once()->withArgs(function (string $path, int $customerId) use (&$temporaryPath, $sourcePath): bool {
            $temporaryPath = $path;
            $this->assertFileExists($path);
            $this->assertNotSame($sourcePath, $path);

            return $customerId === 73;
        })->andReturn([
            'available' => true,
            'fields' => ['first_name' => 'Jane'],
            'confidence' => ['first_name' => 0.9],
            'warnings' => [],
        ]);

        try {
            $file = new UploadedFile($sourcePath, 'passport.png', 'image/png', null, true);
            $request = Request::create('/book/passport-scan', 'POST', ['consent' => '1'], [], [
                'passport_image' => $file,
            ], ['HTTP_ACCEPT' => 'application/json']);
            $request->setUserResolver(fn () => $user);

            $response = (new PassportScanController)($request, $extractor);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('Jane', $response->getData(true)['fields']['first_name']);
            $this->assertFileDoesNotExist($temporaryPath);

            $withoutConsent = Request::create('/book/passport-scan', 'POST', [], [], [
                'passport_image' => new UploadedFile($sourcePath, 'passport.png', 'image/png', null, true),
            ], ['HTTP_ACCEPT' => 'application/json']);
            $withoutConsent->setUserResolver(fn () => $user);
            $this->assertSame(422, (new PassportScanController)($withoutConsent, Mockery::mock(PassportExtraction::class))->getStatusCode());
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_passport_scan_component_renders_a_review_confirmation_control(): void
    {
        $html = Blade::render('<x-passport-scan passenger-index="2" />');

        $this->assertStringContainsString('data-passenger-index="2"', $html);
        $this->assertStringContainsString('data-passport-confirm', $html);
        $this->assertStringContainsString('passport-scan:confirmed', $html);
    }

    private function createTestImage(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'passport-test-');
        $image = imagecreatetruecolor(8, 8);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }
}
