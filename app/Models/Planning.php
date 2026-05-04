<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Planning extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'date_du',
        'date_au',
        'ref_dossier',
        'nbr_personnes',
        'flight',
        'heure',
        'point_depart',
        'site',
        'service_id',
        'supplier_vehicule_id',
        'driver_id',
        'guide_id',
        'destination_id',
        'vehicule_id',
        'budget',
        'supplier_price',
        'notes',
    ];

    protected $casts = [
        'date_du' => 'date',
        'date_au' => 'date',
        'heure' => 'datetime:H:i',
        'budget' => 'decimal:2',
        'supplier_price' => 'decimal:2',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function supplierVehicule()
    {
        return $this->belongsTo(SupplierVehicule::class, 'supplier_vehicule_id');
    }

    public function supplierClient()
    {
        return $this->belongsTo(SupplierClient::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }

    public function destination()
    {
        return $this->belongsTo(Destination::class);
    }

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function planningClients()
    {
        return $this->hasMany(PlanningClient::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'planning_clients');
    }
}
