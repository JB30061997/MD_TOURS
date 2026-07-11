<?php

namespace App\Console\Commands;

use App\Services\PlanningMobileNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDailyMobilePlanningNotifications extends Command
{
    protected $signature = 'mobile:send-today-services {--date=} {--dry-run}';

    protected $description = 'Send mobile push notifications with today services to related app users.';

    public function handle(PlanningMobileNotificationService $notifications): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $summary = $notifications->notifyTodayServices($date, (bool) $this->option('dry-run'));

        $this->info('Daily mobile notification summary:');

        foreach ($summary as $key => $value) {
            $this->line($key . ': ' . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
        }

        return self::SUCCESS;
    }
}
