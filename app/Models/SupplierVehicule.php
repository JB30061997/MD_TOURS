<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierVehicule extends BaseModel
{
    use HasFactory;

    protected $table = 'supplier_vehicules';

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

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
