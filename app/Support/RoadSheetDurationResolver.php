<?php

namespace App\Support;

use App\Models\Planning;
use Illuminate\Support\Facades\Schema;

class RoadSheetDurationResolver
{
    private static ?array $serviceColumns = null;

    public static function resolve(Planning $planning): int
    {
        $service = $planning->service;

        if ($service) {
            self::$serviceColumns ??= Schema::getColumnListing('services');
            foreach (['duration_days', 'duree_jours', 'duration', 'duree'] as $column) {
                if (in_array($column, self::$serviceColumns, true)) {
                    $value = (int) $service->getAttribute($column);

                    if ($value > 0) {
                        return min($value, 365);
                    }
                }
            }

            $nameDuration = self::fromServiceName((string) $service->designation);

            if ($nameDuration !== null) {
                return $nameDuration;
            }
        }

        if ($planning->date_du && $planning->date_au && $planning->date_au->gte($planning->date_du)) {
            return min(((int) $planning->date_du->diffInDays($planning->date_au)) + 1, 365);
        }

        return 1;
    }

    public static function fromServiceName(string $name): ?int
    {
        if (!preg_match('/\b(\d{1,3})\s*(?:jour|jours|day|days)\b/iu', $name, $matches)) {
            return null;
        }

        return max(1, min((int) $matches[1], 365));
    }
}
