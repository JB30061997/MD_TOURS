<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListMissingSupplierPlanningsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'date' => ['nullable', 'date'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'search' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
