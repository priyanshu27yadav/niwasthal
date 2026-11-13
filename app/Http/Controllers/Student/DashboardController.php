<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\RentRecord;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the student dashboard with key information.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Eager load student with property.owner, room, bed, and bed->room
        $student = $user->student()->with(['property.owner', 'room', 'bed', 'bed.room'])->firstOrFail();

        // All monthly rent bills (newest first) with payment allocations
        $rentRecords = RentRecord::where('student_id', $student->id)
            ->with(['allocations.payment'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $currentRent = $rentRecords
            ->where('month', now()->month)
            ->where('year', now()->year)
            ->first();

        $totalOutstanding = (float) $rentRecords
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('outstanding');

        $razorpayConfigured = app(\App\Services\RazorpayService::class)->isConfigured();

        // Last 3 payments made by this student
        $recentPayments = Payment::where('student_id', $student->id)
            ->orderByDesc('paid_at')
            ->limit(3)
            ->get();

        // Count of unread notifications for this user
        $unreadNotificationsCount = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        // Active announcements for the student's property
        $announcements = Announcement::where('property_id', $student->property_id)
            ->where('is_active', true)
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        // Count of pending complaints filed by this student
        $pendingComplaintsCount = Complaint::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'open', 'in_progress'])
            ->count();

        return view('student.dashboard', compact(
            'student',
            'currentRent',
            'rentRecords',
            'totalOutstanding',
            'razorpayConfigured',
            'recentPayments',
            'unreadNotificationsCount',
            'announcements',
            'pendingComplaintsCount'
        ));
    }

    /**
     * Display the student's room details.
     */
    public function room(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $student = $user->student()->with(['property', 'bed', 'bed.room'])->firstOrFail();

        return view('student.room', compact('student'));
    }
}
