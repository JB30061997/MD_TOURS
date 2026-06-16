<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReservateurReservation extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'reservateur_id',
        'service',
        'lieu_depart',
        'lieu_arrivee',
        'date_service',
        'heure_souhaitee',
        'nombre_personnes',
        'contact',
        'informations_complementaires',
        'statut',
    ];

    protected $casts = [
        'date_service' => 'date',
        'heure_souhaitee' => 'datetime:H:i',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReservateurReservation $reservation) {
            if (!$reservation->reference) {
                $reservation->reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'RR' . now()->format('ymd') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function reservateur()
    {
        return $this->belongsTo(Reservateur::class);
    }

    public function canBeChanged(): bool
    {
        return $this->statut !== 'confirmee';
    }
}
