<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'phone',
        'email',
        'address',
        'notes',
    ];

    public function typeSupplier()
    {
        return $this->belongsTo(TypeSupplier::class, 'type');
    }

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    public function user()
{
    return $this->belongsTo(User::class);
}
}