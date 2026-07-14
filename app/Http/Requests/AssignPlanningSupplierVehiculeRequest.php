<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPlanningSupplierVehiculeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plannings.edit') === true;
    }

    public function rules(): array
    {
        return [
            'supplier_vehicule_id' => ['required', 'integer', 'exists:supplier_vehicules,id'],
            'planning_ids' => ['required', 'array', 'min:1', 'max:500'],
            'planning_ids.*' => ['required', 'integer', 'distinct', 'exists:plannings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_vehicule_id.required' => 'Veuillez sélectionner un fournisseur véhicule.',
            'planning_ids.required' => 'Veuillez sélectionner au moins un planning.',
            'planning_ids.min' => 'Veuillez sélectionner au moins un planning.',
        ];
    }
}
