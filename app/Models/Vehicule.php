<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicule extends Model
{
    protected $fillable = [
        'matricule',
        'marque',
        'modele',
        'type',
        'couleur',
        'annee',
        'nombre_places',
        'carburant',
        'boite_vitesse',
        'numero_assurance',
        'date_expiration_assurance',
        'date_visite_technique',
        'date_expiration_visite',
        'status',
        'notes',
    ];
}
