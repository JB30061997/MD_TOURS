<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierVehiculeInvoicePayment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'supplier_vehicule_invoice_id',
        'amount',
        'method',
        'payment_date',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function invoice()
    {
        return $this->belongsTo(SupplierVehiculeInvoice::class, 'supplier_vehicule_invoice_id');
    }
}
