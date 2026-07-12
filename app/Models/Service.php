<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'designation',
        'type_service',
        'description',
    ];

    public function typeService()
    {
        return $this->belongsTo(TypeService::class, 'type_service');
    }

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    public function supplierVehiculeTarifs()
    {
        return $this->hasMany(SupplierVehiculeServiceTarif::class);
    }
}
