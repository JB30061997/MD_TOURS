<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverFuelInvoicePlanning extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'driver_fuel_invoice_id',
        'planning_id',
        'is_selected',
        'notes',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
    ];

    // relation m3a facture consommation
    public function driverFuelInvoice()
    {
        return $this->belongsTo(DriverFuelInvoice::class);
    }

    // relation m3a planning
    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }
}
