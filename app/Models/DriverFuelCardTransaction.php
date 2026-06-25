<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverFuelCardTransaction extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'driver_fuel_card_id',
        'type',
        'amount',
        'transaction_date',
        'reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function fuelCard()
    {
        return $this->belongsTo(DriverFuelCard::class, 'driver_fuel_card_id');
    }
}
