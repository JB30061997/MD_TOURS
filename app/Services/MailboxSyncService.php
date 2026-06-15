<?php

namespace App\Services;

use App\Models\MailAccount;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Webklex\IMAP\Facades\Client;

class MailboxSyncService
{
    public function __construct(private ReservationDraftParser $reservationParser)
    {
    }

    public function syncUser(User $user, int $limit = 50): array
    {
        if (!$this->hasMailCredentials($user)) {
            return [
                'synced' => 0,
                'drafts' => 0,
                'skipped' => true,
                'message' => 'Mailbox non configurée.',
            ];
        }

        $account = MailAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'email' => $user->mail_integration_login,
            ],
            [
                'name' => $user->name . ' Mailbox',
                'username' => $user->mail_integration_login,
                'imap_host' => config('mailbox.imap.host'),
                'imap_port' => config('mailbox.imap.port'),
                'imap_encryption' => config('mailbox.imap.encryption'),
                'smtp_host' => config('mailbox.smtp.host'),
                'smtp_port' => config('mailbox.smtp.port'),
                'smtp_encryption' => config('mailbox.smtp.encryption'),
                'is_active' => true,
            ]
        );

        $client = Client::make([
            'host' => config('mailbox.imap.host'),
            'port' => config('mailbox.imap.port'),
            'encryption' => config('mailbox.imap.encryption'),
            'validate_cert' => config('mailbox.imap.validate_cert'),
            'username' => $user->mail_integration_login,
            'password' => $user->mail_integration_password,
            'protocol' => 'imap',
        ]);

        $client->connect();
        $folder = $client->getFolder('INBOX');
        $messages = $folder->messages()->all()->limit($limit)->get();
        $synced = 0;
        $drafts = 0;

        foreach ($messages as $mail) {
            $subject = mb_decode_mimeheader($mail->getSubject() ?? '(No subject)');
            $from = $mail->getFrom()->first();
            $messageId = $mail->getMessageId() ?: md5($subject . $mail->getDate() . optional($from)->mail);

            $mailMessage = MailMessage::where('mail_account_id', $account->id)
                ->where('message_id', $messageId)
                ->first();

            if ($mailMessage) {
                $draft = $this->reservationParser->createOrUpdateFromMessage($mailMessage);

                if ($draft && $draft->status === 'pending') {
                    $drafts++;
                }

                continue;
            }

            $htmlBody = $mail->getHTMLBody();
            $textBody = $mail->getTextBody();

            $mailMessage = MailMessage::create([
                'mail_account_id' => $account->id,
                'message_id' => $messageId,
                'folder' => 'inbox',
                'from_name' => optional($from)->personal,
                'from_email' => optional($from)->mail,
                'to_email' => $user->mail_integration_login,
                'subject' => $subject,
                'body_text' => $textBody ?: strip_tags($htmlBody),
                'body_html' => $htmlBody,
                'is_read' => false,
                'received_at' => $mail->getDate() ?: now(),
            ]);

            foreach ($mail->getAttachments() as $attachment) {
                $fileName = $attachment->getName() ?: 'attachment_' . uniqid();
                $safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\.\-]/', '_', $fileName);
                $storagePath = 'mail_attachments/' . $mailMessage->id . '/' . $safeName;
                $content = $attachment->getContent();

                Storage::disk('public')->put($storagePath, $content);

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
                        ['cid:' . $cid, 'cid:<' . $cleanCid . '>', 'cid:' . $cleanCid],
                        asset('storage/' . $storagePath),
                        $htmlBody
                    );
                }
            }

            $mailMessage->update([
                'body_html' => $htmlBody,
                'body_text' => $textBody ?: strip_tags($htmlBody),
            ]);

            $draft = $this->reservationParser->createOrUpdateFromMessage($mailMessage);

            if ($draft && $draft->status === 'pending') {
                $drafts++;
            }

            $synced++;
        }

        $client->disconnect();

        return [
            'synced' => $synced,
            'drafts' => $drafts,
            'skipped' => false,
            'message' => "{$synced} email(s) synchronisé(s), {$drafts} réservation(s) détectée(s).",
        ];
    }

    private function hasMailCredentials(User $user): bool
    {
        return (bool) (
            $user->mail_integrate
            && $user->mail_integration_login
            && $user->mail_integration_password
        );
    }
}
