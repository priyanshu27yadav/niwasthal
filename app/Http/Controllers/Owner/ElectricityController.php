<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ElectricityAllocation;
use App\Models\ElectricityBill;
use App\Models\Property;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ElectricityController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = ElectricityBill::with(['property', 'allocations'])
            ->whereIn('property_id', $propertyIds);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bills = $query->orderByDesc('year')->orderByDesc('month')->paginate(15)->withQueryString();
        $properties = Property::where('owner_id', $owner->id)->get();

        return view('owner.electricity.index', compact('bills', 'properties'));
    }

    public function create()
    {
        $owner = Auth::user();
        $properties = Property::where('owner_id', $owner->id)->get();
        $months = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => date('F', mktime(0, 0, 0, $m, 1))]);
        $years  = collect(range(date('Y') - 2, date('Y') + 1))->mapWithKeys(fn($y) => [$y => $y]);

        return view('owner.electricity.create', compact('properties', 'months', 'years'));
    }

    public function store(Request $request)
    {
        $owner = Auth::user();

        $validated = $request->validate([
            'property_id'   => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($owner) {
                    if (!Property::where('id', $value)->where('owner_id', $owner->id)->exists()) {
                        $fail('The selected property does not belong to you.');
                    }
                },
            ],
            'month'         => 'required|integer|between:1,12',
            'year'          => 'required|integer|min:2000|max:2099',
            'total_units'   => 'required|numeric|min:0',
            'rate_per_unit' => 'required|numeric|min:0',
            'fixed_charges' => 'nullable|numeric|min:0',
            'split_type'    => 'required|in:equal,by_unit,manual',
            'notes'         => 'nullable|string|max:1000',
        ]);

        $exists = ElectricityBill::where('property_id', $validated['property_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['month' => 'An electricity bill for this property, month, and year already exists.']);
        }

        $fixedCharges = (float) ($validated['fixed_charges'] ?? 0);
        $totalAmount  = ($validated['total_units'] * $validated['rate_per_unit']) + $fixedCharges;

        $bill = DB::transaction(function () use ($validated, $fixedCharges, $totalAmount) {
            $bill = ElectricityBill::create([
                'property_id'   => $validated['property_id'],
                'month'         => $validated['month'],
                'year'          => $validated['year'],
                'total_units'   => $validated['total_units'],
                'rate_per_unit' => $validated['rate_per_unit'],
                'fixed_charges' => $fixedCharges,
                'total_amount'  => $totalAmount,
                'split_type'    => $validated['split_type'],
                'status'        => 'draft',
                'notes'         => $validated['notes'] ?? null,
            ]);

            if ($validated['split_type'] === 'equal') {
                $this->generateEqualAllocations($bill);
            }

            return $bill;
        });

        return redirect()
            ->route('owner.electricity.show', $bill)
            ->with('success', 'Electricity bill created successfully.');
    }

    public function show(ElectricityBill $electricity)
    {
        $this->authorizeOwnership($electricity->property);
        $electricity->load(['property', 'allocations.student']);

        return view('owner.electricity.show', compact('electricity'));
    }

    public function generateAllocations(Request $request, ElectricityBill $electricity)
    {
        $this->authorizeOwnership($electricity->property);

        DB::transaction(function () use ($electricity) {
            $electricity->allocations()->delete();
            $this->generateEqualAllocations($electricity);
        });

        return back()->with('success', 'Student electricity allocations generated successfully.');
    }

    public function markPaid(Request $request, ElectricityAllocation $allocation)
    {
        $allocation->load('bill.property');
        $this->authorizeOwnership($allocation->bill->property);

        $allocation->update([
            'paid_amount' => $allocation->amount,
            'is_paid'     => true,
        ]);

        $bill = $allocation->bill;
        $allPaid = $bill->allocations()->where('is_paid', false)->doesntExist();
        if ($allPaid) {
            $bill->update(['status' => 'paid']);
        }

        return back()->with('success', 'Allocation marked as paid.');
    }

    public function destroy(ElectricityBill $electricity)
    {
        $this->authorizeOwnership($electricity->property);

        if ($electricity->allocations()->where('is_paid', true)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete a bill that has paid allocations.']);
        }

        DB::transaction(function () use ($electricity) {
            $electricity->allocations()->delete();
            $electricity->delete();
        });

        return redirect()
            ->route('owner.electricity.index')
            ->with('success', 'Electricity bill deleted successfully.');
    }

    private function generateEqualAllocations(ElectricityBill $bill): void
    {
        $activeStudents = Student::where('property_id', $bill->property_id)
            ->where('status', 'active')
            ->get();

        if ($activeStudents->isEmpty()) {
            return;
        }

        $count            = $activeStudents->count();
        $perStudentAmount = round($bill->total_amount / $count, 2);
        $perStudentUnits  = round($bill->total_units / $count, 2);
        $remainder        = round($bill->total_amount - ($perStudentAmount * $count), 2);

        $allocations = [];
        $now = now();
        foreach ($activeStudents as $index => $student) {
            $amount = $perStudentAmount + ($index === $count - 1 ? $remainder : 0);
            $allocations[] = [
                'bill_id'      => $bill->id,
                'student_id'   => $student->id,
                'student_name' => $student->name,
                'units'        => $perStudentUnits,
                'amount'       => $amount,
                'paid_amount'  => 0,
                'is_paid'      => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        ElectricityAllocation::insert($allocations);
        $bill->update(['status' => 'generated']);
    }

    private function authorizeOwnership(Property $property): void
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
