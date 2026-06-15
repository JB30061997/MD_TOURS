<?php

namespace App\Http\Controllers;

use App\Models\MailAccount;
use App\Models\MailMessage;
use App\Services\MailboxSyncService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MailboxController extends Controller
{
    private function imapConfig(): array
    {
        return config('mailbox.imap');
    }

    private function smtpConfig(): array
    {
        return config('mailbox.smtp');
    }

    public function index(Request $request)
    {
        $folder = $request->get('folder', 'inbox');
        $search = $request->get('search');
        $account = $this->currentMailAccount($request);

        $messages = MailMessage::with(['account', 'attachments'])
            ->when($account, fn ($query) => $query->where('mail_account_id', $account->id))
            ->when(!$account, fn ($query) => $query->whereRaw('1 = 0'))
            ->where('folder', $folder)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%")
                        ->orWhere('from_email', 'like', "%{$search}%")
                        ->orWhere('body_text', 'like', "%{$search}%");
                });
            })
            ->latest('received_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Mailbox/Index', [
            'messages' => $messages,
            'filters' => [
                'folder' => $folder,
                'search' => $search,
            ],
            'counts' => [
                'inbox' => $this->messageCount($account, 'inbox'),
                'sent' => $this->messageCount($account, 'sent'),
                'draft' => $this->messageCount($account, 'draft'),
                'unread' => $account
                    ? MailMessage::where('mail_account_id', $account->id)->where('folder', 'inbox')->where('is_read', false)->count()
                    : 0,
            ],
            'mailIntegration' => [
                'enabled' => (bool) $request->user()?->mail_integrate,
                'login' => $request->user()?->mail_integration_login,
                'ready' => $this->hasMailCredentials($request->user()),
            ],
        ]);
    }

    public function show(Request $request, MailMessage $message)
    {
        abort_unless($message->account?->user_id === $request->user()?->id, 404);

        $message->update(['is_read' => true]);
        $message->load(['account', 'attachments']);

        $message->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/' . $attachment->path);
            return $attachment;
        });

        return Inertia::render('Mailbox/Show', [
            'message' => $message,
        ]);
    }

    public function seedDemo()
    {
        $user = auth()->user();

        if (!$user) {
            abort(403);
        }

        $account = MailAccount::firstOrCreate(
            [
                'user_id' => $user->id,
                'email' => $user->mail_integration_login ?: $user->email,
            ],
            [
                'name' => $user->name . ' Mailbox',
                'username' => $user->mail_integration_login ?: $user->email,
                'is_active' => true,
            ]
        );

        $demoMessages = [
            [
                'folder' => 'inbox',
                'from_name' => 'Client Exemple',
                'from_email' => 'client@example.com',
                'to_email' => $account->email,
                'subject' => 'Demande de devis transport',
                'body_text' => 'Bonjour, merci de nous envoyer un devis pour un transfert aéroport.',
                'is_read' => false,
                'received_at' => now()->subMinutes(20),
            ],
            [
                'folder' => 'inbox',
                'from_name' => 'Agence Test',
                'from_email' => 'agence@test.com',
                'to_email' => $account->email,
                'subject' => 'Confirmation réservation',
                'body_text' => 'Bonjour, nous confirmons la réservation pour demain matin.',
                'is_read' => true,
                'received_at' => now()->subHours(2),
            ],
        ];

        foreach ($demoMessages as $message) {
            MailMessage::firstOrCreate(
                [
                    'mail_account_id' => $account->id,
                    'subject' => $message['subject'],
                    'from_email' => $message['from_email'],
                ],
                array_merge($message, [
                    'mail_account_id' => $account->id,
                ])
            );
        }

        return redirect()
            ->route('mailbox.index')
            ->with('success', 'Demo mailbox créée avec succès.');
    }

    public function sync(MailboxSyncService $mailboxSync)
    {
        set_time_limit(180);
        $user = auth()->user();

        if (!$this->hasMailCredentials($user)) {
            return back()->with('error', 'Veuillez configurer la boîte mail de cet admin avant la synchronisation.');
        }

        try {
            $result = $mailboxSync->syncUser($user);

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur sync mail: ' . $e->getMessage());
        }
    }

    private function currentMailAccount(Request $request): ?MailAccount
    {
        $user = $request->user();

        if (!$user || !$user->mail_integrate || !$user->mail_integration_login) {
            return null;
        }

        return MailAccount::where('user_id', $user->id)
            ->where('email', $user->mail_integration_login)
            ->first();
    }

    private function hasMailCredentials($user): bool
    {
        return (bool) (
            $user
            && $user->mail_integrate
            && $user->mail_integration_login
            && $user->mail_integration_password
        );
    }

    private function messageCount(?MailAccount $account, string $folder): int
    {
        if (!$account) {
            return 0;
        }

        return MailMessage::where('mail_account_id', $account->id)
            ->where('folder', $folder)
            ->count();
    }
}
