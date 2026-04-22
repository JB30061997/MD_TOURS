<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Planning extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'date_du',
        'date_au',
        'ref_dossier',
        'bus',
        'nbr_personnes',
        'flight',
        'heure',
        'point_depart',
        'destination',
        'site',
        'service_id',
        'supplier_id',
        'driver_id',
        'guide_id',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
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