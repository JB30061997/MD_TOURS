<?php

namespace App\Console\Commands;

use App\Http\Controllers\SupplierVehiculeTarifController;
use App\Services\SupplierVehiculeTarifSyncer;
use Illuminate\Console\Command;

class SyncSupplierVehiculeTarifsFromPlannings extends Command
{
    protected $signature = 'supplier-vehicule-tarifs:sync-from-plannings {--overwrite : Replace existing non-zero tariffs with the latest planning supplier price}';

    protected $description = 'Fill vehicle supplier contractual tariffs from existing planning supplier prices.';

    public function handle(SupplierVehiculeTarifController $controller, SupplierVehiculeTarifSyncer $syncer): int
    {
        $controller->ensureTarifsTableExists();

        $result = $syncer->syncFromPlannings((bool) $this->option('overwrite'));

        $this->info("Pairs found: {$result['pairs_found']}");
        $this->info("Created: {$result['created']}");
        $this->info("Updated: {$result['updated']}");
        $this->info("Skipped: {$result['skipped']}");

        return self::SUCCESS;
    }
}
