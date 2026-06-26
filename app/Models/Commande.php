<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commande extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'voucher_number',
        'start_date',
        'end_date',
        'service_id',
        'supplier_price',
        'start_point',
        'start_point_flight',
        'start_point_city',
        'start_point_time',
        'end_point',
        'end_point_flight',
        'end_point_city',
        'end_point_time',
        'driver_id',
        'vehicule_id',
        'guide_id',
        'passenger',
        'number_pax',
        'reference',
        'date',
        'signature',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'date' => 'date',
        'supplier_price' => 'decimal:2',
        'number_pax' => 'integer',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }
}
