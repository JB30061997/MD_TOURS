<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierClient extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'email',
        'address',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
