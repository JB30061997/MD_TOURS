<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeService extends BaseModel
{
    use HasFactory;

    protected $table = 'type_services';

    protected $fillable = [
        'designation',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'type_service');
    }
}