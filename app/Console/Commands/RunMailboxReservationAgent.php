<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MailboxSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunMailboxReservationAgent extends Command
{
    protected $signature = 'mailbox:reservation-agent {--limit=50}';

    protected $description = 'Sync admin mailboxes and prepare reservation drafts for validation.';

    public function handle(MailboxSyncService $mailboxSync): int
    {
        $limit = (int) $this->option('limit');
        $users = User::query()
            ->where('mail_integrate', true)
            ->whereNotNull('mail_integration_login')
            ->whereNotNull('mail_integration_password')
            ->get();

        $totalSynced = 0;
        $totalDrafts = 0;

        foreach ($users as $user) {
            try {
                $result = $mailboxSync->syncUser($user, $limit);
                $totalSynced += (int) $result['synced'];
                $totalDrafts += (int) $result['drafts'];

                $this->info("{$user->email}: {$result['message']}");
            } catch (\Throwable $e) {
                Log::warning('Mailbox reservation agent failed', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);

                $this->warn("{$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Synced={$totalSynced}, reservation_drafts={$totalDrafts}");

        return self::SUCCESS;
    }
}
