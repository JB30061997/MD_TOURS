<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanningServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plannings.edit') === true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'replace_confirmed' => ['required', 'boolean'],
        ];
    }
}
