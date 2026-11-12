<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DepositTransaction;
use App\Models\Property;
use App\Models\SecurityDeposit;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = SecurityDeposit::with(['student', 'property', 'transactions'])
            ->whereIn('property_id', $propertyIds);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhereHas('student', fn ($sq) => $sq->where('mobile', 'like', "%{$search}%"));
            });
        }

        $deposits   = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $properties = Property::where('owner_id', $owner->id)->get();
        $students   = Student::with('property')->whereIn('property_id', $propertyIds)->where('status', 'active')->orderBy('name')->get();

        $totalDeposits   = SecurityDeposit::whereIn('property_id', $propertyIds)->sum('amount');
        $totalBalance    = SecurityDeposit::whereIn('property_id', $propertyIds)->sum('balance');
        $totalDeductions = max(0, $totalDeposits - $totalBalance);

        return view('owner.deposits.index', compact(
            'deposits',
            'properties',
            'students',
            'totalDeposits',
            'totalBalance',
            'totalDeductions'
        ));
    }

    public function store(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $validated = $request->validate([
            'student_id'   => 'required|integer|exists:students,id',
            'amount'       => 'required|numeric|min:1',
            'collected_on' => 'required|date',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $student = Student::where('id', $validated['student_id'])
            ->whereIn('property_id', $propertyIds)
            ->firstOrFail();

        $deposit = DB::transaction(function () use ($student, $validated) {
            $deposit = SecurityDeposit::create([
                'student_id'   => $student->id,
                'property_id'  => $student->property_id,
                'student_name' => $student->name,
                'amount'       => $validated['amount'],
                'balance'      => $validated['amount'],
                'collected_on' => $validated['collected_on'],
                'status'       => 'held',
                'notes'        => $validated['notes'] ?? null,
            ]);

            DepositTransaction::create([
                'deposit_id'       => $deposit->id,
                'type'             => 'collect',
                'amount'           => $validated['amount'],
                'reason'           => 'Initial security deposit collected.',
                'transaction_date' => $validated['collected_on'],
            ]);

            return $deposit;
        });

        return redirect()
            ->route('owner.deposits.show', $deposit)
            ->with('success', 'Security deposit created successfully.');
    }

    public function addTransaction(Request $request, SecurityDeposit $deposit)
    {
        $this->authorizeOwnership($deposit->property);

        $validated = $request->validate([
            'type'             => 'required|in:deduct,refund',
            'amount'           => 'required|numeric|min:0.01|max:' . $deposit->balance,
            'transaction_date' => 'required|date',
            'reason'           => 'required|string|max:500',
            'reference'        => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($deposit, $validated) {
            $newBalance = max(0, (float) $deposit->balance - (float) $validated['amount']);
            $status = $newBalance <= 0 ? 'refunded' : 'partially_refunded';

            $deposit->update([
                'balance' => $newBalance,
                'status'  => $status,
            ]);

            DepositTransaction::create([
                'deposit_id'       => $deposit->id,
                'type'             => $validated['type'],
                'amount'           => $validated['amount'],
                'reason'           => $validated['reason'],
                'transaction_date' => $validated['transaction_date'],
                'reference'        => $validated['reference'] ?? null,
            ]);
        });

        return back()->with('success', 'Deposit transaction recorded successfully.');
    }

    public function show(SecurityDeposit $deposit)
    {
        $this->authorizeOwnership($deposit->property);
        $deposit->load(['student', 'property', 'transactions']);

        return view('owner.deposits.show', compact('deposit'));
    }

    private function authorizeOwnership(Property $property): void
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
