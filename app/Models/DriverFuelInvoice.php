<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverFuelInvoice extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
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

    // relation m3a driver
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // relation m3a planningat المرتابطين بهاد facture
    public function planningLinks()
    {
        return $this->hasMany(DriverFuelInvoicePlanning::class);
    }

    // direct access l plannings
    public function plannings()
    {
        return $this->belongsToMany(
            Planning::class,
            'driver_fuel_invoice_plannings',
            'driver_fuel_invoice_id',
            'planning_id'
        )->withPivot('is_selected', 'notes')->withTimestamps();
    }
}