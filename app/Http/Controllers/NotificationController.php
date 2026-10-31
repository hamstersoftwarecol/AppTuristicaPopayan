<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $request->validate(['unread' => ['nullable', 'boolean']]);

        return Inertia::render('Notifications', [
            'notifications' => $request->user()->notifications()
                ->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
                ->latest()->paginate(20)->withQueryString(),
            'onlyUnread' => $request->boolean('unread'),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $notice = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $notice->markAsRead();

        return back()->with('success', 'Notificación marcada como leída.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Tus notificaciones quedaron marcadas como leídas.');
    }
}
