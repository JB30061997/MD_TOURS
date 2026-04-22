<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeSupplier extends BaseModel
{
    use HasFactory;

    protected $table = 'type_suppliers';

    protected $fillable = [
        'designation',
        'description',
    ];

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'type');
    }
}