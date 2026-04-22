<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guide extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'status',
        'notes',
    ];

    public function plannings()
    {
        return $this->hasMany(Planning::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
