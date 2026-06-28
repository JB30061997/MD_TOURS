<?php

namespace App\Console\Commands;

use App\Http\Controllers\SupplierVehiculeTarifController;
use App\Services\SupplierVehiculeTarifSyncer;
use Illuminate\Console\Command;

class SyncSupplierVehiculeTarifsFromPlannings extends Command
{
    protected $signature = 'supplier-vehicule-tarifs:sync-from-plannings {--no-overwrite : Keep existing non-zero tariffs instead of replacing them with the latest planning supplier price}';

    protected $description = 'Fill vehicle supplier contractual tariffs from existing planning supplier prices.';

    public function handle(SupplierVehiculeTarifController $controller, SupplierVehiculeTarifSyncer $syncer): int
    {
        $controller->ensureTarifsTableExists();

        $result = $syncer->syncFromPlannings(!$this->option('no-overwrite'));

        $this->info("Period: {$result['date_from']} -> {$result['date_to']}");
        $this->info("Plannings analyzed: {$result['plannings_analyzed']}");
        $this->info("Valid plannings: {$result['valid_plannings']}");
        $this->info("Suppliers processed: {$result['suppliers_processed']}");
        $this->info("Unique tariff keys found: {$result['pairs_found']}");
        $this->info("Created: {$result['created']}");
        $this->info("Updated: {$result['updated']}");
        $this->info("Unchanged: {$result['unchanged']}");
        $this->info("Existing kept: {$result['skipped_existing']}");
        $this->info("Duplicates ignored: {$result['duplicates_ignored']}");
        $this->info("Ignored missing vehicle seats: {$result['ignored_missing_seats']}");

        return self::SUCCESS;
    }
}
