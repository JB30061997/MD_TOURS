<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class Destination extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'country',
        'type',
        'status',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function destinationPlannings()
    {
        return $this->hasMany(Planning::class, 'destination_id');
    }
}