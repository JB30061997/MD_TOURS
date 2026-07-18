<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Planning;
use Illuminate\Database\Eloquent\Builder;

class DriverPlanningService
{
    public function query(Driver $driver, array $relations = []): Builder
    {
        return Planning::query()
            ->select('plannings.*')
            ->distinct()
            ->when($relations, fn (Builder $query) => $query->with($relations))
            ->where('plannings.driver_id', $driver->id);
    }

    public function applyPeriod(Builder $query, ?string $period, ?string $date = null): Builder
    {
        $date ??= today()->toDateString();

        return $query->when($period, function (Builder $query, string $period) use ($date) {
            if ($period === 'today') {
                $query->whereDate('plannings.date_du', $date);
            } elseif ($period === 'past') {
                $query->whereDate('plannings.date_du', '<', $date);
            } elseif ($period === 'upcoming') {
                $query->whereDate('plannings.date_du', '>', $date);
            }
        });
    }

    public function stats(Driver $driver, ?string $date = null): array
    {
        $date ??= today()->toDateString();
        $base = $this->query($driver);

        return [
            'total' => (clone $base)->count('plannings.id'),
            'past' => (clone $base)->whereDate('plannings.date_du', '<', $date)->count('plannings.id'),
            'today' => (clone $base)->whereDate('plannings.date_du', $date)->count('plannings.id'),
            'upcoming' => (clone $base)->whereDate('plannings.date_du', '>', $date)->count('plannings.id'),
        ];
    }
}
