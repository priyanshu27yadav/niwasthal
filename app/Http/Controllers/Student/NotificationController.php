<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated student, paginated.
     * Also marks all notifications as read upon viewing the page.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Retrieve paginated notifications, newest first
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        // Mark all currently unread notifications as read
        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $unreadCount = 0; // All are now read after visiting this page

        return view('student.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark a single notification as read via POST (AJAX).
     */
    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Ensure the notification belongs to this user
        abort_if($notification->user_id !== $user->id, 403, 'Access denied.');

        $notification->markAsRead();

        return response()->json([
            'success'  => true,
            'message'  => 'Notification marked as read.',
            'read_at'  => $notification->read_at?->toISOString(),
        ]);
    }
}
