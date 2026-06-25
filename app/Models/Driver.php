<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'status',
        'notes',
    ];

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    public function vehicleAssignments()
    {
        return $this->hasMany(DriverVehicleAssignment::class);
    }

    public function currentVehicleAssignment()
    {
        return $this->hasOne(DriverVehicleAssignment::class)
            ->whereNull('released_date')
            ->latestOfMany();
    }

    public function fuelCards()
    {
        return $this->hasMany(DriverFuelCard::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
