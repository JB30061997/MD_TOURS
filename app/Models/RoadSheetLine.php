<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoadSheetLine extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'road_sheet_id',
        'date',
        'departure_kms',
        'arrival_kms',
        'distance',
        'gasoline',
        'jawaz',
        'other_expenses',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'date' => 'date',
        'gasoline' => 'decimal:2',
        'jawaz' => 'decimal:2',
        'other_expenses' => 'decimal:2',
    ];

    public function roadSheet()
    {
        return $this->belongsTo(RoadSheet::class);
    }
}
