<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DriverVehicleAssignment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicule_id',
        'assigned_date',
        'released_date',
        'notes',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'released_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }
}
