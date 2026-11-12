<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ElectricityAllocation;
use Illuminate\View\View;

class ElectricityController extends Controller
{
    /**
     * List all electricity bill allocations for the authenticated student.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $student = $user->student()->with(['property', 'bed', 'bed.room'])->firstOrFail();

        $allocations = ElectricityAllocation::where('student_id', $student->id)
            ->with(['bill'])
            ->orderByDesc('created_at')
            ->paginate(12);

        // Summary totals
        $totalUnits  = $allocations->sum('units');
        $totalAmount = $allocations->sum('amount');
        $totalPaid   = $allocations->sum('paid_amount');
        $totalDue    = $totalAmount - $totalPaid;

        return view('student.electricity.index', compact(
            'student',
            'allocations',
            'totalUnits',
            'totalAmount',
            'totalPaid',
            'totalDue'
        ));
    }
}
