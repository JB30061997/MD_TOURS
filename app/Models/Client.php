<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'supplier_client_id',
        'phone',
        'email',
        'notes',
    ];

    public function supplierClient()
    {
        return $this->belongsTo(SupplierClient::class);
    }

    public function planningClients()
    {
        return $this->hasMany(PlanningClient::class);
    }

    public function plannings()
    {
        return $this->belongsToMany(Planning::class, 'planning_clients');
    }
}
