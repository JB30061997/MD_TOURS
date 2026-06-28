<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierVehiculeServiceTarif extends BaseModel
{
    use HasFactory;

    protected $table = 'supplier_vehicule_service_tarifs';

    protected $fillable = [
        'supplier_vehicule_id',
        'service_id',
        'type_service_id',
        'vehicle_seats',
        'price',
    ];

    protected $casts = [
        'vehicle_seats' => 'integer',
        'price' => 'decimal:2',
    ];

    public function supplierVehicule()
    {
        return $this->belongsTo(SupplierVehicule::class, 'supplier_vehicule_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function typeService()
    {
        return $this->belongsTo(TypeService::class, 'type_service_id');
    }
}
