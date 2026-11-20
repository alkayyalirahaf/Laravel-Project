<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AiService
{
    public const TIPS = [
        'check_documents' => 'Check passport and entry requirements with the relevant authorities before travel.',
        'check_baggage' => 'Check the baggage allowance for your selected cabin with the airline.',
        'verify_itinerary' => 'Verify your airports, travel dates and flight times before booking.',
        'arrive_early' => 'Ask your airline when to arrive at the airport and allow time for airport procedures.',
        'check_transport' => 'Check transport options between your arrival airport and destination.',
    ];

    public function available(): bool
    {
        $endpoint = config('services.dev3_ai.endpoint');

        return is_string($endpoint) && filter_var($endpoint, FILTER_VALIDATE_URL)
            && parse_url($endpoint, PHP_URL_SCHEME) === 'https'
            && is_string(config('services.dev3_ai.key')) && config('services.dev3_ai.key') !== '';
    }

    public function reply(string $mode, string $message): array
    {
        if (!in_array($mode, ['find', 'compare', 'tips'], true)) {
            throw new \InvalidArgumentException('Unsupported assistant mode.');
        }
        if (!$this->available()) {
            throw new \RuntimeException('AI integration is unavailable because a provider has not been configured.');
        }

        try {
            // The configured gateway may select only these approved tips. It has no
            // booking/payment tools and its free text, prices and IDs are never used.
            $response = Http::withToken(config('services.dev3_ai.key'))->acceptJson()
                ->connectTimeout(4)->timeout(12)->post(config('services.dev3_ai.endpoint'), [
                    'mode' => $mode, 'message' => $message,
                    'allowed_tip_keys' => array_keys(self::TIPS),
                    'instruction' => 'Return a JSON object containing tip_keys: an array of one to five allowed keys. Do not return prices, flight IDs, booking or payment instructions.',
                ]);
        } catch (ConnectionException $exception) {
            throw new \RuntimeException('The AI provider could not be reached. Please try again later.');
        }

        if (!$response->successful()) {
            throw new \RuntimeException('The AI provider is temporarily unavailable.');
        }

        $keys = $response->json('tip_keys');
        if (!is_array($keys) || !array_is_list($keys) || count($keys) < 1 || count($keys) > 5) {
            throw new \RuntimeException('The AI provider returned an unsupported response.');
        }

        $tips = [];
        foreach ($keys as $key) {
            if (!is_string($key) || !array_key_exists($key, self::TIPS)) {
                throw new \RuntimeException('The AI provider returned an unsupported response.');
            }
            $tips[] = self::TIPS[$key];
        }

        return array_values(array_unique($tips));
    }
}
