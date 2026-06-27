<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $commande = $this->route('commande');
        $commandeId = is_object($commande) ? $commande->id : $commande;

        return [
            'planning_id' => ['nullable', 'exists:plannings,id'],
            'supplier_client_id' => ['nullable', 'exists:supplier_clients,id'],
            'supplier_vehicule_id' => ['nullable', 'exists:supplier_vehicules,id'],
            'voucher_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('commandes', 'voucher_number')->ignore($commandeId),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'service_id' => ['nullable', 'exists:services,id'],
            'supplier_price' => ['nullable', 'numeric', 'min:0'],
            'start_point' => ['nullable', 'string', 'max:255'],
            'start_point_flight' => ['nullable', 'string', 'max:255'],
            'start_point_city' => ['nullable', 'string', 'max:255'],
            'start_point_time' => ['nullable', 'date_format:H:i'],
            'end_point' => ['nullable', 'string', 'max:255'],
            'end_point_flight' => ['nullable', 'string', 'max:255'],
            'end_point_city' => ['nullable', 'string', 'max:255'],
            'end_point_time' => ['nullable', 'date_format:H:i'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'vehicule_id' => ['nullable', 'exists:vehicules,id'],
            'guide_id' => ['nullable', 'exists:guides,id'],
            'passenger' => ['nullable', 'string', 'max:255'],
            'number_pax' => ['nullable', 'integer', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'signature' => ['nullable', 'string', 'max:255'],
        ];
    }
}
