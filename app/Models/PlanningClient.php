<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanningClient extends BaseModel
{
    use HasFactory;

    protected $table = 'planning_clients';

    protected $fillable = [
        'planning_id',
        'client_id',
    ];

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}