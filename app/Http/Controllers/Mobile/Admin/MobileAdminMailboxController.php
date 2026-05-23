<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailMessage;
use Illuminate\Http\Request;

class MobileAdminMailboxController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $folder = $request->get('folder', 'inbox');
        $search = trim((string) $request->get('search', ''));

        $messages = MailMessage::with(['account', 'attachments'])
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
                'inbox' => MailMessage::where('folder', 'inbox')->count(),
                'sent' => MailMessage::where('folder', 'sent')->count(),
                'draft' => MailMessage::where('folder', 'draft')->count(),
                'unread' => MailMessage::where('folder', 'inbox')->where('is_read', false)->count(),
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
}
