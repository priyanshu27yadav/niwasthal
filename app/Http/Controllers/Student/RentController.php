<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\RentRecord;
use Illuminate\View\View;

class RentController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student()->firstOrFail();

        $rentRecords = RentRecord::where('student_id', $student->id)
            ->with(['allocations.payment'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->paginate(12);

        $totalOutstanding = RentRecord::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('outstanding');

        $razorpayConfigured = app(\App\Services\RazorpayService::class)->isConfigured();

        return view('student.rent.index', compact('student', 'rentRecords', 'totalOutstanding', 'razorpayConfigured'));
    }

    public function show(RentRecord $rent): View
    {
        $student = auth()->user()->student()->firstOrFail();

        if ($rent->student_id !== $student->id) {
            abort(403, 'Unauthorized access to rent record.');
        }

        $rent->load(['allocations.payment', 'property']);

        return view('student.rent.show', compact('student', 'rent'));
    }
}
