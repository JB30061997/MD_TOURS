<?php

namespace App\Http\Controllers;

use App\Models\MailAccount;
use App\Models\MailMessage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Webklex\IMAP\Facades\Client;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $folder = $request->get('folder', 'inbox');
        $search = $request->get('search');

        $messages = MailMessage::with(['account', 'attachments'])
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
                'inbox' => MailMessage::where('folder', 'inbox')->count(),
                'sent' => MailMessage::where('folder', 'sent')->count(),
                'draft' => MailMessage::where('folder', 'draft')->count(),
                'unread' => MailMessage::where('folder', 'inbox')->where('is_read', false)->count(),
            ],
        ]);
    }

    public function show(MailMessage $message)
    {
        $message->update(['is_read' => true]);

        return Inertia::render('Mailbox/Show', [
            'message' => $message->load(['account', 'attachments']),
        ]);
    }

    public function seedDemo()
    {
        $account = MailAccount::firstOrCreate(
            ['email' => 'contact@md-tours.ma'],
            [
                'name' => 'MD Tours Mailbox',
                'username' => 'contact@md-tours.ma',
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
            [
                'folder' => 'sent',
                'from_name' => 'MD Tours',
                'from_email' => $account->email,
                'to_email' => 'client@example.com',
                'subject' => 'Réponse devis transport',
                'body_text' => 'Bonjour, veuillez trouver notre proposition de tarif.',
                'is_read' => true,
                'received_at' => now()->subDay(),
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



    public function sync()
    {
        set_time_limit(120);

        $account = MailAccount::where('is_active', true)->first();

        if (!$account) {
            return back()->with('error', 'Aucun compte mail actif trouvé.');
        }

        try {
            $client = Client::make([
                'host'          => $account->imap_host ?: 'imap.gmail.com',
                'port'          => $account->imap_port ?: 993,
                'encryption'    => $account->imap_encryption ?: 'ssl',
                'validate_cert' => false,
                'username'      => $account->username,
                'password'      => $account->password,
                'protocol'      => 'imap',
            ]);

            $client->connect();

            $folder = $client->getFolder('INBOX');

            $messages = $folder->messages()->all()->get();

            $count = 0;

            foreach ($messages as $mail) {
                $messageId = $mail->getMessageId();

                if (!$messageId) {
                    $messageId = md5($mail->getSubject() . $mail->getDate());
                }

                if (MailMessage::where('message_id', $messageId)->exists()) {
                    continue;
                }

                MailMessage::create([
                    'mail_account_id' => $account->id,
                    'message_id'      => $messageId,
                    'folder'          => 'inbox',
                    'from_name'       => optional($mail->getFrom()->first())->personal,
                    'from_email'      => optional($mail->getFrom()->first())->mail,
                    'to_email'        => $account->email,
                    'subject'         => $mail->getSubject(),
                    'body_text'       => 'Email synchronisé depuis Gmail. Le contenu sera ajouté après optimisation.',
                    'body_html'       => null,
                    'is_read'         => false,
                    'received_at'     => $mail->getDate(),
                ]);

                $count++;
            }

            $client->disconnect();

            return back()->with('success', "{$count} email(s) synchronisé(s).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur sync mail: ' . $e->getMessage());
        }
    }
}
