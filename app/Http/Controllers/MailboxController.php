<?php

namespace App\Http\Controllers;

use App\Models\MailAccount;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $account = MailAccount::firstOrCreate(
            ['email' => env('IMAP_USERNAME')],
            [
                'name' => 'MD Tours Mailbox',
                'username' => env('IMAP_USERNAME'),
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

    public function sync()
    {
        set_time_limit(180);

        $account = MailAccount::firstOrCreate(
            ['email' => env('IMAP_USERNAME')],
            [
                'name' => env('MAIL_FROM_NAME', 'MD TOURS'),
                'username' => env('IMAP_USERNAME'),
                'is_active' => true,
            ]
        );

        try {
            $client = Client::make([
                'host' => env('IMAP_HOST'),
                'port' => env('IMAP_PORT', 993),
                'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
                'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', false), FILTER_VALIDATE_BOOLEAN),
                'username' => env('IMAP_USERNAME'),
                'password' => env('IMAP_PASSWORD'),
                'protocol' => 'imap',
            ]);

            $client->connect();

            $folder = $client->getFolder('INBOX');

            // matjbedch kolchi f kol sync, ghir latest 50
            $messages = $folder->messages()
                ->all()
                ->limit(50)
                ->get();

            $count = 0;

            foreach ($messages as $mail) {
                $subject = mb_decode_mimeheader($mail->getSubject() ?? '(No subject)');
                $messageId = $mail->getMessageId();

                if (!$messageId) {
                    $messageId = md5($subject . $mail->getDate() . optional($mail->getFrom()->first())->mail);
                }

                if (MailMessage::where('message_id', $messageId)->exists()) {
                    continue;
                }

                $htmlBody = $mail->getHTMLBody();
                $textBody = $mail->getTextBody();

                $from = $mail->getFrom()->first();

                $mailMessage = MailMessage::create([
                    'mail_account_id' => $account->id,
                    'message_id' => $messageId,
                    'folder' => 'inbox',
                    'from_name' => optional($from)->personal,
                    'from_email' => optional($from)->mail,
                    'to_email' => env('IMAP_USERNAME'),
                    'subject' => $subject,
                    'body_text' => $textBody ?: strip_tags($htmlBody),
                    'body_html' => $htmlBody,
                    'is_read' => false,
                    'received_at' => $mail->getDate() ?: now(),
                ]);

                foreach ($mail->getAttachments() as $attachment) {
                    $fileName = $attachment->getName() ?: 'attachment_' . uniqid();
                    $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $fileName);

                    $folderPath = 'mail_attachments/' . $mailMessage->id;
                    $storagePath = $folderPath . '/' . $safeName;

                    $content = $attachment->getContent();

                    Storage::disk('public')->put($storagePath, $content);

                    $url = asset('storage/' . $storagePath);

                    MailAttachment::create([
                        'mail_message_id' => $mailMessage->id,
                        'file_name' => $fileName,
                        'mime_type' => $attachment->getMimeType(),
                        'size' => strlen($content),
                        'path' => $storagePath,
                    ]);

                    $cid = $attachment->getId();

                    if ($cid && $htmlBody) {
                        $cleanCid = trim($cid, '<>');

                        $htmlBody = str_replace(
                            [
                                'cid:' . $cid,
                                'cid:<' . $cleanCid . '>',
                                'cid:' . $cleanCid,
                            ],
                            $url,
                            $htmlBody
                        );
                    }
                }

                $mailMessage->update([
                    'body_html' => $htmlBody,
                    'body_text' => $textBody ?: strip_tags($htmlBody),
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
