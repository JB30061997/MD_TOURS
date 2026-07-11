<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverPrimeFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ids = $this->input('type_service_ids', []);
        $this->merge([
            'type_service_ids' => is_array($ids) ? array_values(array_filter($ids)) : [$ids],
        ]);
    }

    public function rules(): array
    {
        $required = $this->routeIs('driver-primes.pdf') ? ['required', 'array', 'min:1'] : ['nullable', 'array'];

        return [
            'type_service_ids' => $required,
            'type_service_ids.*' => ['integer', 'distinct', 'exists:type_services,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }
}
