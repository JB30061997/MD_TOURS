<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierVehiculeInvoice extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'supplier_vehicule_id',
        'period_start',
        'period_end',
        'invoice_number',
        'invoice_date',
        'total_amount',
        'pdf_path',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * relation m3a fournisseur véhicule
     */
    public function supplierVehicule()
    {
        return $this->belongsTo(SupplierVehicule::class);
    }

    /**
     * relation m3a table pivot (details dyal plannings)
     */
    public function invoicePlannings()
    {
        return $this->hasMany(SupplierVehiculeInvoicePlanning::class);
    }

    /**
     * relation directe m3a plannings
     */
    public function plannings()
    {
        return $this->belongsToMany(
            Planning::class,
            'supplier_vehicule_invoice_plannings'
        )
        ->withPivot('is_selected', 'notes')
        ->withTimestamps();
    }
}