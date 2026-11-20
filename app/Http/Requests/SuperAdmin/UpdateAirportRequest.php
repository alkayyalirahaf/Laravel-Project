<?php

namespace App\Http\Requests\SuperAdmin;

use App\Models\Airport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAirportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');

        if (is_string($code)) {
            $this->merge(['code' => strtoupper(trim($code))]);
        }
    }

    public function rules(): array
    {
        $airport = $this->route('airport');
        $uniqueCode = Rule::unique('airports', 'code');

        if ($airport instanceof Airport) {
            $uniqueCode->ignoreModel($airport);
        } elseif (is_int($airport) || (is_string($airport) && ctype_digit($airport))) {
            $uniqueCode->ignore($airport);
        }

        return [
            'code' => ['required', 'string', 'regex:/^[A-Z]{3}$/', $uniqueCode],
            'name' => ['required', 'string', 'max:150'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Enter a three-letter airport code.',
            'code.regex' => 'The airport code must contain exactly three letters.',
            'code.unique' => 'That airport code is already in use.',
            'name.required' => 'Enter the airport name.',
            'name.max' => 'The airport name may not exceed 150 characters.',
            'city_id.required' => 'Select a city.',
            'city_id.exists' => 'Select an existing city.',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'airport code',
            'name' => 'airport name',
            'city_id' => 'city',
        ];
    }
}
