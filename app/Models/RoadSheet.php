<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoadSheet extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'planning_id',
        'voucher_number',
        'start_city',
        'end_city',
        'start_flight',
        'end_flight',
        'start_time',
        'end_time',
        'signature_date',
        'signature_name',
        'notes',
        'status',
    ];

    protected $appends = [
        'status_label',
    ];

    protected $casts = [
        'signature_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    public function lines()
    {
        return $this->hasMany(RoadSheetLine::class)->orderBy('sort_order');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'renseignee' => 'Renseignée',
            default => 'À compléter',
        };
    }
}
