<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservateur extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'telephone',
        'email',
        'adresse',
        'statut',
    ];

    protected static function booted(): void
    {
        static::creating(function (Reservateur $reservateur) {
            if (!$reservateur->reference) {
                $reservateur->reference = static::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'RS' . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function reservations()
    {
        return $this->hasMany(ReservateurReservation::class);
    }

    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }
}
