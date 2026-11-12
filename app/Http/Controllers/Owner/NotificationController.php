<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Notification;
use App\Models\Property;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('owner.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function sendAnnouncement(Request $request)
    {
        $owner = Auth::user();

        $validated = $request->validate([
            'property_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!Property::where('id', $value)->where('owner_id', $owner->id)->exists()) {
                        $fail('The selected property does not belong to you.');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'body'  => 'required|string|max:5000',
        ]);

        $students = Student::where('property_id', $validated['property_id'])
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->get();

        DB::transaction(function () use ($owner, $validated, $students) {
            $announcement = Announcement::create([
                'property_id'  => $validated['property_id'],
                'owner_id'     => $owner->id,
                'title'        => $validated['title'],
                'body'         => $validated['body'],
                'is_active'    => true,
                'published_at' => now(),
            ]);

            $now = now();
            $notifications = $students->map(fn ($s) => [
                'user_id'    => $s->user_id,
                'type'       => 'info',
                'title'      => $validated['title'],
                'body'       => $validated['body'],
                'data'       => json_encode(['announcement_id' => $announcement->id]),
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            if (!empty($notifications)) {
                Notification::insert($notifications);
            }
        });

        return redirect()
            ->route('owner.notifications.announcements')
            ->with('success', "Announcement published and sent to {$students->count()} student(s).");
    }

    public function announcements(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $announcements = Announcement::with('property')
            ->whereIn('property_id', $propertyIds)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $properties = Property::where('owner_id', $owner->id)->get();

        return view('owner.notifications.announcements', compact('announcements', 'properties'));
    }
}
