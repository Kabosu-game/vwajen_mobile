<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Notifications\ActivityNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $filter = $request->query('filter');
        $notifications = $user->notifications()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($filter === 'mentions', fn ($q) => $q->where('data', 'like', '%"type":"mention"%'))
            ->when($filter === 'civic', fn ($q) => $q->where(fn ($w) => collect(['question', 'candidate_answer', 'question_answered', 'debate', 'live', 'event'])
                ->each(fn ($t) => $w->orWhere('data', 'like', '%"type":"'.$t.'"%'))))
            ->latest()->paginate(30)->withQueryString();

        return view('notifications.index', compact('notifications', 'filter'));
    }

    public function count(Request $request)
    {
        $user = $request->user();
        $latest = $user->unreadNotifications()->latest()->first();

        return response()->json([
            'notifications' => $user->unreadNotifications()->count(),
            'messages' => $user->unreadMessagesCount(),
            'latest' => $latest ? [
                'id' => $latest->id,
                'text' => ActivityNotification::render($latest->data['text_key'] ?? '', $latest->data['params'] ?? []),
                'url' => $latest->data['url'] ?? null,
            ] : null,
        ]);
    }

    public function read(Request $request, string $id)
    {
        $n = $request->user()->notifications()->findOrFail($id);
        $n->markAsRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect()->to($n->data['url'] ?? route('notifications.index'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->reply($request, ['ok' => true], __('Toutes les notifications sont marquées comme lues.'));
    }

    public function destroy(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->delete();

        return $this->reply($request, ['ok' => true]);
    }

    /** Abonnement push (navigateur / mobile). */
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string', 'max:20'],
        ]);
        PushSubscription::updateOrCreate(['endpoint' => $data['endpoint']], [
            'user_id' => $request->user()->id,
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
        ]);

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        PushSubscription::where('user_id', $request->user()->id)->where('endpoint', $request->input('endpoint'))->delete();

        return response()->json(['ok' => true]);
    }
}
