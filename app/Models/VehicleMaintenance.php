<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleMaintenance extends Model
{
    protected $fillable = [
        'vehicule_id',
        'type_maintenance',
        'date_maintenance',
        'kilometrage',
        'montant',
        'garage',
        'prochaine_date',
        'prochain_kilometrage',
        'status',
        'notes',
    ];

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }
}
