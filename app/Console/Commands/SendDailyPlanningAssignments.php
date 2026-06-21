<?php

namespace App\Console\Commands;

use App\Mail\DailyPlanningAssignmentsMail;
use App\Models\Driver;
use App\Models\Planning;
use App\Models\SupplierVehicule;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyPlanningAssignments extends Command
{
    protected $signature = 'plannings:send-daily-assignments {--date=} {--dry-run}';

    protected $description = 'Send next-day assigned plannings to each driver and vehicle supplier.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->addDay()->startOfDay();

        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        $this->info('Planning assignment date: ' . $date->toDateString());

        Driver::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderBy('name')
            ->chunkById(100, function (EloquentCollection $drivers) use ($date, $dryRun, &$sent) {
                foreach ($drivers as $driver) {
                    $plannings = $this->planningsForDate($date)
                        ->where('driver_id', $driver->id)
                        ->get();

                    if ($plannings->isEmpty()) {
                        continue;
                    }

                    $this->sendMail($driver->email, $driver->name, 'driver', $date, $plannings, $dryRun);
                    $sent++;
                }
            });

        SupplierVehicule::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('is_active', true)
            ->orderBy('name')
            ->chunkById(100, function (EloquentCollection $suppliers) use ($date, $dryRun, &$sent) {
                foreach ($suppliers as $supplier) {
                    $plannings = $this->planningsForDate($date)
                        ->where('supplier_vehicule_id', $supplier->id)
                        ->get();

                    if ($plannings->isEmpty()) {
                        continue;
                    }

                    $this->sendMail($supplier->email, $supplier->name, 'supplier_vehicule', $date, $plannings, $dryRun);
                    $sent++;
                }
            });

        $this->info(($dryRun ? 'Dry run complete. ' : 'Done. ') . "Recipients with plannings: {$sent}");

        return self::SUCCESS;
    }

    private function planningsForDate(Carbon $date)
    {
        return Planning::query()
            ->with([
                'service',
                'destination',
                'driver',
                'supplierVehicule',
                'vehicule',
                'guide',
                'planningClients.client',
            ])
            ->where(function ($query) use ($date) {
                $query->whereDate('date_du', $date)
                    ->orWhere(function ($rangeQuery) use ($date) {
                        $rangeQuery
                            ->whereDate('date_du', '<=', $date)
                            ->whereNotNull('date_au')
                            ->whereDate('date_au', '>=', $date);
                    });
            })
            ->orderBy('heure')
            ->orderBy('id');
    }

    private function sendMail(
        string $email,
        string $name,
        string $type,
        Carbon $date,
        EloquentCollection $plannings,
        bool $dryRun
    ): void {
        if ($dryRun) {
            $this->line("DRY RUN: {$type} {$name} <{$email}> => {$plannings->count()} planning(s)");
            return;
        }

        try {
            Mail::to($email)->send(new DailyPlanningAssignmentsMail($name, $type, $date, $plannings));
            $this->line("Sent: {$type} {$name} <{$email}> => {$plannings->count()} planning(s)");
        } catch (\Throwable $exception) {
            Log::error('Daily planning assignment email failed', [
                'email' => $email,
                'type' => $type,
                'date' => $date->toDateString(),
                'error' => $exception->getMessage(),
            ]);

            $this->error("Failed: {$type} {$name} <{$email}>");
        }
    }
}
