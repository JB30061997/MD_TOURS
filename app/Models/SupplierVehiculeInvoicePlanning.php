<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierVehiculeInvoicePlanning extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'supplier_vehicule_invoice_id',
        'planning_id',
        'is_selected',
        'notes',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    /**
     * relation m3a facture
     */
    public function invoice()
    {
        return $this->belongsTo(SupplierVehiculeInvoice::class, 'supplier_vehicule_invoice_id');
    }

    /**
     * relation m3a planning
     */
    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }
}
