<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiPassportExtractor implements PassportExtraction
{
    private const FIELDS = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'nationality',
        'passport_number',
        'passport_country',
        'passport_expiry',
    ];

    /** Common MRZ alpha-2/alpha-3 codes mapped to the passenger form's text values. */
    private const COUNTRY_CODES = [
        'AE' => ['United Arab Emirates', 'Emirati'], 'ARE' => ['United Arab Emirates', 'Emirati'],
        'AU' => ['Australia', 'Australian'], 'AUS' => ['Australia', 'Australian'],
        'BD' => ['Bangladesh', 'Bangladeshi'], 'BGD' => ['Bangladesh', 'Bangladeshi'],
        'BH' => ['Bahrain', 'Bahraini'], 'BHR' => ['Bahrain', 'Bahraini'],
        'BR' => ['Brazil', 'Brazilian'], 'BRA' => ['Brazil', 'Brazilian'],
        'CA' => ['Canada', 'Canadian'], 'CAN' => ['Canada', 'Canadian'],
        'CH' => ['Switzerland', 'Swiss'], 'CHE' => ['Switzerland', 'Swiss'],
        'CN' => ['China', 'Chinese'], 'CHN' => ['China', 'Chinese'],
        'DE' => ['Germany', 'German'], 'DEU' => ['Germany', 'German'],
        'DK' => ['Denmark', 'Danish'], 'DNK' => ['Denmark', 'Danish'],
        'EG' => ['Egypt', 'Egyptian'], 'EGY' => ['Egypt', 'Egyptian'],
        'ES' => ['Spain', 'Spanish'], 'ESP' => ['Spain', 'Spanish'],
        'ET' => ['Ethiopia', 'Ethiopian'], 'ETH' => ['Ethiopia', 'Ethiopian'],
        'FI' => ['Finland', 'Finnish'], 'FIN' => ['Finland', 'Finnish'],
        'FR' => ['France', 'French'], 'FRA' => ['France', 'French'],
        'GB' => ['United Kingdom', 'British'], 'GBR' => ['United Kingdom', 'British'],
        'GH' => ['Ghana', 'Ghanaian'], 'GHA' => ['Ghana', 'Ghanaian'],
        'GR' => ['Greece', 'Greek'], 'GRC' => ['Greece', 'Greek'],
        'ID' => ['Indonesia', 'Indonesian'], 'IDN' => ['Indonesia', 'Indonesian'],
        'IN' => ['India', 'Indian'], 'IND' => ['India', 'Indian'],
        'IQ' => ['Iraq', 'Iraqi'], 'IRQ' => ['Iraq', 'Iraqi'],
        'IR' => ['Iran', 'Iranian'], 'IRN' => ['Iran', 'Iranian'],
        'IT' => ['Italy', 'Italian'], 'ITA' => ['Italy', 'Italian'],
        'JO' => ['Jordan', 'Jordanian'], 'JOR' => ['Jordan', 'Jordanian'],
        'JP' => ['Japan', 'Japanese'], 'JPN' => ['Japan', 'Japanese'],
        'KE' => ['Kenya', 'Kenyan'], 'KEN' => ['Kenya', 'Kenyan'],
        'KW' => ['Kuwait', 'Kuwaiti'], 'KWT' => ['Kuwait', 'Kuwaiti'],
        'LB' => ['Lebanon', 'Lebanese'], 'LBN' => ['Lebanon', 'Lebanese'],
        'LK' => ['Sri Lanka', 'Sri Lankan'], 'LKA' => ['Sri Lanka', 'Sri Lankan'],
        'MA' => ['Morocco', 'Moroccan'], 'MAR' => ['Morocco', 'Moroccan'],
        'MX' => ['Mexico', 'Mexican'], 'MEX' => ['Mexico', 'Mexican'],
        'MY' => ['Malaysia', 'Malaysian'], 'MYS' => ['Malaysia', 'Malaysian'],
        'NG' => ['Nigeria', 'Nigerian'], 'NGA' => ['Nigeria', 'Nigerian'],
        'NL' => ['Netherlands', 'Dutch'], 'NLD' => ['Netherlands', 'Dutch'],
        'NO' => ['Norway', 'Norwegian'], 'NOR' => ['Norway', 'Norwegian'],
        'NP' => ['Nepal', 'Nepalese'], 'NPL' => ['Nepal', 'Nepalese'],
        'NZ' => ['New Zealand', 'New Zealander'], 'NZL' => ['New Zealand', 'New Zealander'],
        'OM' => ['Oman', 'Omani'], 'OMN' => ['Oman', 'Omani'],
        'PH' => ['Philippines', 'Filipino'], 'PHL' => ['Philippines', 'Filipino'],
        'PK' => ['Pakistan', 'Pakistani'], 'PAK' => ['Pakistan', 'Pakistani'],
        'PS' => ['Palestine', 'Palestinian'], 'PSE' => ['Palestine', 'Palestinian'],
        'QA' => ['Qatar', 'Qatari'], 'QAT' => ['Qatar', 'Qatari'],
        'RU' => ['Russia', 'Russian'], 'RUS' => ['Russia', 'Russian'],
        'SA' => ['Saudi Arabia', 'Saudi'], 'SAU' => ['Saudi Arabia', 'Saudi'],
        'SE' => ['Sweden', 'Swedish'], 'SWE' => ['Sweden', 'Swedish'],
        'SG' => ['Singapore', 'Singaporean'], 'SGP' => ['Singapore', 'Singaporean'],
        'SY' => ['Syria', 'Syrian'], 'SYR' => ['Syria', 'Syrian'],
        'TH' => ['Thailand', 'Thai'], 'THA' => ['Thailand', 'Thai'],
        'TN' => ['Tunisia', 'Tunisian'], 'TUN' => ['Tunisia', 'Tunisian'],
        'TR' => ['Türkiye', 'Turkish'], 'TUR' => ['Türkiye', 'Turkish'],
        'UA' => ['Ukraine', 'Ukrainian'], 'UKR' => ['Ukraine', 'Ukrainian'],
        'US' => ['United States', 'American'], 'USA' => ['United States', 'American'],
        'VN' => ['Vietnam', 'Vietnamese'], 'VNM' => ['Vietnam', 'Vietnamese'],
        'YE' => ['Yemen', 'Yemeni'], 'YEM' => ['Yemen', 'Yemeni'],
        'ZA' => ['South Africa', 'South African'], 'ZAF' => ['South Africa', 'South African'],
    ];

    public function extract(string $privateFilePath, int $customerId): array
    {
        if ($customerId < 1 || ! is_file($privateFilePath) || ! is_readable($privateFilePath)) {
            throw $this->unavailable();
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($privateFilePath);
        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw $this->unreadable();
        }

        $image = @file_get_contents($privateFilePath);
        if (! is_string($image) || $image === '') {
            throw $this->unavailable();
        }

        $apiKey = config('services.gemini.key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw $this->unavailable();
        }

        $model = config('services.gemini.model', 'gemini-2.5-flash');
        if (! is_string($model) || ! preg_match('/^[A-Za-z0-9._-]+$/', $model)) {
            throw $this->unavailable();
        }

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $this->prompt()],
                    ['inline_data' => ['mime_type' => $mimeType, 'data' => base64_encode($image)]],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->responseSchema(),
            ],
        ];

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', $payload);
        } catch (ConnectionException $exception) {
            throw $this->unavailable($exception);
        } catch (Throwable $exception) {
            throw $this->unavailable($exception);
        }

        if (! $response->successful()) {
            throw $this->unavailable();
        }

        $text = collect(data_get($response->json(), 'candidates.0.content.parts', []))
            ->pluck('text')
            ->filter(fn (mixed $part): bool => is_string($part))
            ->implode("\n");

        try {
            $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw $this->unreadable($exception);
        }

        if (! is_array($decoded) || ! is_array($decoded['fields'] ?? null) || ! is_array($decoded['confidence'] ?? null)) {
            throw $this->unreadable();
        }

        $fields = [];
        $confidence = [];

        foreach (self::FIELDS as $field) {
            $score = $this->normalizeConfidence($decoded['confidence'][$field] ?? null);
            $value = $this->normalizeField($field, $decoded['fields'][$field] ?? null);
            $fields[$field] = $value;
            $confidence[$field] = $value === null ? 0.0 : $score;
        }

        $warnings = [];
        $mrzMismatches = $this->mrzMismatches($decoded['mrz_lines'] ?? null);

        foreach ($mrzMismatches as $field) {
            $confidence[$field] = round($confidence[$field] * 0.5, 2);
        }

        if (! array_filter($fields, static fn (?string $value): bool => $value !== null)) {
            throw $this->unreadable();
        }

        if ($mrzMismatches !== []) {
            $warnings[] = 'Some machine-readable-zone checks did not match. Please verify every suggested value against the passport.';
        }

        $lowConfidenceFields = array_filter(
            self::FIELDS,
            fn (string $field): bool => $fields[$field] !== null && $confidence[$field] < 0.75,
        );
        if ($lowConfidenceFields !== []) {
            $warnings[] = 'Some values may be difficult to read. Please verify every suggested value against the passport.';
        }

        return [
            'available' => true,
            'fields' => $fields,
            'confidence' => $confidence,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function prompt(): string
    {
        return <<<'PROMPT'
Read the passport data page and its machine-readable zone (MRZ). Extract only information that is legible on the document; never infer, complete, translate from memory, or guess. Return null for each unreadable or ambiguous value. Return JSON only, following the supplied schema. Dates must be Gregorian YYYY-MM-DD. Gender must be "male" for M or "female" for F; return null for any other or unclear marker. Return nationality and passport-country as the printed value or the ISO 3166-1 alpha-3 code actually visible in the MRZ. For every field, include confidence from 0.0 to 1.0. Include the two MRZ lines exactly as read, or null if they cannot be read confidently.
PROMPT;
    }

    private function responseSchema(): array
    {
        $fieldProperties = [];
        $confidenceProperties = [];

        foreach (self::FIELDS as $field) {
            $fieldProperties[$field] = ['type' => 'STRING', 'nullable' => true];
            $confidenceProperties[$field] = ['type' => 'NUMBER'];
        }

        return [
            'type' => 'OBJECT',
            'properties' => [
                'fields' => [
                    'type' => 'OBJECT',
                    'properties' => $fieldProperties,
                    'required' => self::FIELDS,
                ],
                'confidence' => [
                    'type' => 'OBJECT',
                    'properties' => $confidenceProperties,
                    'required' => self::FIELDS,
                ],
                'mrz_lines' => [
                    'type' => 'ARRAY',
                    'nullable' => true,
                    'items' => ['type' => 'STRING'],
                ],
            ],
            'required' => ['fields', 'confidence', 'mrz_lines'],
        ];
    }

    private function normalizeField(string $field, mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return match ($field) {
            'first_name', 'last_name' => $this->normalizeName($value),
            'date_of_birth' => $this->normalizeDate($value, mustBePast: true),
            'passport_expiry' => $this->normalizeDate($value),
            'gender' => $this->normalizeGender($value),
            'nationality', 'passport_country' => $this->normalizeCountryText($value, $field),
            'passport_number' => $this->normalizePassportNumber($value),
            default => null,
        };
    }

    private function normalizeName(string $value): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if ($value === '' || mb_strlen($value) > 100 || ! preg_match('/^[\p{L}\p{M}][\p{L}\p{M}\s\'.-]*$/u', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizeDate(string $value, bool $mustBePast = false): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value), new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== trim($value)
            || ($mustBePast && $date >= new DateTimeImmutable('today', new DateTimeZone('UTC')))) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    private function normalizeGender(string $value): ?string
    {
        return match (strtolower(trim($value))) {
            'm', 'male', 'man' => 'male',
            'f', 'female', 'woman' => 'female',
            default => null,
        };
    }

    private function normalizeCountryText(string $value, string $field): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        $code = strtoupper($value);

        if (isset(self::COUNTRY_CODES[$code])) {
            return self::COUNTRY_CODES[$code][$field === 'nationality' ? 1 : 0];
        }

        if (preg_match('/^[A-Z]{2,3}$/', $code)) {
            return $code;
        }

        if ($value === '' || mb_strlen($value) > 60
            || ! preg_match('/^[\p{L}\p{M}][\p{L}\p{M}\s\'’.-]*$/u', $value)) {
            return null;
        }

        return $value;
    }

    private function normalizePassportNumber(string $value): ?string
    {
        $value = strtoupper(preg_replace('/[\s-]+/', '', trim($value)) ?? '');

        return preg_match('/^[A-Z0-9]{1,20}$/', $value) ? $value : null;
    }

    private function normalizeConfidence(mixed $value): float
    {
        if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0 || (float) $value > 1) {
            return 0.0;
        }

        return round((float) $value, 2);
    }

    /** @return array<int, string> */
    private function mrzMismatches(mixed $lines): array
    {
        if (! is_array($lines) || count($lines) < 2 || ! is_string($lines[1] ?? null)) {
            return [];
        }

        $line = strtoupper(preg_replace('/\s+/', '', $lines[1]) ?? '');
        if (strlen($line) !== 44) {
            return [];
        }

        $checks = [
            'passport_number' => [substr($line, 0, 9), $line[9]],
            'date_of_birth' => [substr($line, 13, 6), $line[19]],
            'passport_expiry' => [substr($line, 21, 6), $line[27]],
        ];
        $mismatches = [];

        foreach ($checks as $field => [$data, $digit]) {
            $expected = $this->mrzCheckDigit($data);
            if ($expected !== null && ctype_digit($digit) && $expected !== (int) $digit) {
                $mismatches[] = $field;
            }
        }

        return $mismatches;
    }

    private function mrzCheckDigit(string $value): ?int
    {
        $weights = [7, 3, 1];
        $sum = 0;

        foreach (str_split($value) as $index => $character) {
            if (ctype_digit($character)) {
                $number = (int) $character;
            } elseif ($character >= 'A' && $character <= 'Z') {
                $number = ord($character) - ord('A') + 10;
            } elseif ($character === '<') {
                $number = 0;
            } else {
                return null;
            }

            $sum += $number * $weights[$index % 3];
        }

        return $sum % 10;
    }

    private function unreadable(?Throwable $previous = null): PassportExtractionException
    {
        return new PassportExtractionException(
            PassportExtractionException::UNREADABLE,
            'We could not read enough passport details. Please enter them manually.',
            $previous,
        );
    }

    private function unavailable(?Throwable $previous = null): PassportExtractionException
    {
        return new PassportExtractionException(
            PassportExtractionException::UNAVAILABLE,
            'Passport scanning is temporarily unavailable. Please enter the details manually.',
            $previous,
        );
    }
}
