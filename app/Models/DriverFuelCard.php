<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverFuelCard extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'card_number',
        'label',
        'balance',
        'status',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function transactions()
    {
        return $this->hasMany(DriverFuelCardTransaction::class);
    }
}
