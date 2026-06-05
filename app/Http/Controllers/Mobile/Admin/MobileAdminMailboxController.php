<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailAccount;
use App\Models\MailMessage;
use Illuminate\Http\Request;

class MobileAdminMailboxController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $folder = $request->get('folder', 'inbox');
        $search = trim((string) $request->get('search', ''));
        $account = $this->currentMailAccount($request);

        $messages = MailMessage::with(['account', 'attachments'])
            ->when($account, fn ($query) => $query->where('mail_account_id', $account->id))
            ->when(!$account, fn ($query) => $query->whereRaw('1 = 0'))
            ->where('folder', $folder)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'like', "%{$search}%")
                        ->orWhere('from_name', 'like', "%{$search}%")
                        ->orWhere('from_email', 'like', "%{$search}%")
                        ->orWhere('body_text', 'like', "%{$search}%");
                });
            })
            ->latest('received_at')
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'messages' => $messages->items(),
            'counts' => [
                'inbox' => $this->messageCount($account, 'inbox'),
                'sent' => $this->messageCount($account, 'sent'),
                'draft' => $this->messageCount($account, 'draft'),
                'unread' => $account
                    ? MailMessage::where('mail_account_id', $account->id)->where('folder', 'inbox')->where('is_read', false)->count()
                    : 0,
            ],
            'mail_integration' => [
                'enabled' => (bool) $request->user()?->mail_integrate,
                'login' => $request->user()?->mail_integration_login,
                'ready' => $this->hasMailCredentials($request->user()),
            ],
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
                'has_more' => $messages->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, MailMessage $message)
    {
        $this->authorizeAdmin($request);

        abort_unless($message->account?->user_id === $request->user()?->id, 404);

        $message->load(['account', 'attachments']);

        $message->attachments->transform(function ($attachment) {
            $attachment->url = asset('storage/' . $attachment->path);
            return $attachment;
        });

        return response()->json([
            'message' => $message,
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        $roles = $user && method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];

        abort_unless(in_array('admin', $roles, true) || in_array('administrateur', $roles, true), 403);
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
