<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteProtection
{
    public static function blockingMessage(string $entityLabel, array $checks): ?string
    {
        $references = [];

        foreach ($checks as $check) {
            $count = self::countReference($check);

            if ($count > 0) {
                $references[] = $count . ' ' . $check['label'];
            }
        }

        if ($references === []) {
            return null;
        }

        return "Impossible de supprimer {$entityLabel}. Il est lié à "
            . implode(', ', $references)
            . ". Veuillez d'abord remplacer, détacher ou archiver ces données.";
    }

    public static function foreignKeyMessage(string $entityLabel, QueryException $exception): string
    {
        return "Impossible de supprimer {$entityLabel}. Il est encore lié à des données existantes. "
            . "Veuillez d'abord remplacer, détacher ou archiver ces données.";
    }

    private static function countReference(array $check): int
    {
        if (isset($check['count']) && is_callable($check['count'])) {
            return (int) $check['count']();
        }

        $table = $check['table'] ?? null;
        $column = $check['column'] ?? null;

        if (!$table || !$column || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where($column, $check['value'])
            ->count();
    }
}
