<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RentController extends Controller
{
    public function index(Request $request)
    {
        $propertyIds = $this->ownerPropertyIds();

        $query = RentRecord::whereIn('property_id', $propertyIds)
            ->with(['student', 'property'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('status');

        if ($month = $request->input('month')) {
            $query->where('month', (int) $month);
        }
        if ($year = $request->input('year')) {
            $query->where('year', (int) $year);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($propertyId = $request->input('property_id')) {
            if (in_array($propertyId, $propertyIds)) {
                $query->where('property_id', $propertyId);
            }
        }
        if ($search = $request->input('search')) {
            $query->where('student_name', 'like', "%{$search}%");
        }

        $rentRecords = $query->paginate(20)->withQueryString();
        $properties  = Property::where('owner_id', Auth::id())->get();

        $totalRent = RentRecord::whereIn('property_id', $propertyIds)
            ->when($request->month,       fn ($q) => $q->where('month',       $request->month))
            ->when($request->year,        fn ($q) => $q->where('year',        $request->year))
            ->when($request->status,      fn ($q) => $q->where('status',      $request->status))
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->selectRaw('SUM(total_amount) as total_billed, SUM(paid_amount) as total_paid, SUM(outstanding) as total_outstanding')
            ->first();

        return view('owner.rent.index', compact('rentRecords', 'properties', 'totalRent'));
    }

    public function create()
    {
        $propertyIds = $this->ownerPropertyIds();
        $properties  = Property::whereIn('id', $propertyIds)->get();
        $students    = Student::whereIn('property_id', $propertyIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('owner.rent.create', compact('properties', 'students'));
    }

    public function store(Request $request)
    {
        $propertyIds = $this->ownerPropertyIds();

        $validated = $request->validate([
            'student_id'  => 'required|exists:students,id',
            'month'       => 'required|integer|between:1,12',
            'year'        => 'required|integer|min:2000|max:2100',
            'rent_amount' => 'required|numeric|min:0',
            'late_fee'    => 'nullable|numeric|min:0',
            'due_date'    => 'nullable|date',
            'notes'       => 'nullable|string|max:500',
        ]);

        $student = Student::with(['room', 'bed.room'])
            ->where('id', $validated['student_id'])
            ->whereIn('property_id', $propertyIds)
            ->firstOrFail();

        $existing = RentRecord::where('student_id', $student->id)
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->first();

        if ($existing) {
            return back()->withInput()->withErrors([
                'month' => "A rent record for {$student->name} for {$validated['month']}/{$validated['year']} already exists.",
            ]);
        }

        $lateFee = (float) ($validated['late_fee'] ?? 0);
        $total   = (float) $validated['rent_amount'] + $lateFee;

        $rentRecord = RentRecord::create([
            'student_id'   => $student->id,
            'property_id'  => $student->property_id,
            'student_name' => $student->name,
            'room_number'  => $student->room_number,
            'month'        => $validated['month'],
            'year'         => $validated['year'],
            'rent_amount'  => $validated['rent_amount'],
            'late_fee'     => $lateFee,
            'discount'     => 0,
            'total_amount' => $total,
            'paid_amount'  => 0,
            'outstanding'  => $total,
            'status'       => 'unpaid',
            'due_date'     => $validated['due_date'] ?? null,
            'notes'        => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('owner.rent.show', $rentRecord)
            ->with('success', 'Rent record created successfully.');
    }

    public function generateMonthly(Request $request)
    {
        $validated = $request->validate([
            'month'       => 'required|integer|between:1,12',
            'year'        => 'required|integer|min:2000|max:2100',
            'property_id' => 'nullable|integer',
        ]);

        $propertyIds = $this->ownerPropertyIds();

        if (!empty($validated['property_id'])) {
            if (!in_array($validated['property_id'], $propertyIds)) {
                abort(403);
            }
            $propertyIds = [$validated['property_id']];
        }

        $students = Student::with(['room', 'bed.room'])
            ->whereIn('property_id', $propertyIds)
            ->where('status', 'active')
            ->get();

        $generated = 0;
        $skipped   = 0;

        DB::transaction(function () use ($students, $validated, &$generated, &$skipped) {
            foreach ($students as $student) {
                $exists = RentRecord::where('student_id', $student->id)
                    ->where('month', $validated['month'])
                    ->where('year', $validated['year'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                RentRecord::create([
                    'student_id'   => $student->id,
                    'property_id'  => $student->property_id,
                    'student_name' => $student->name,
                    'room_number'  => $student->room_number,
                    'month'        => $validated['month'],
                    'year'         => $validated['year'],
                    'rent_amount'  => $student->monthly_rent,
                    'late_fee'     => 0,
                    'discount'     => 0,
                    'total_amount' => $student->monthly_rent,
                    'paid_amount'  => 0,
                    'outstanding'  => $student->monthly_rent,
                    'status'       => 'unpaid',
                    'due_date'     => \Carbon\Carbon::create($validated['year'], $validated['month'], 5)->toDateString(),
                ]);

                $generated++;
            }
        });

        return redirect()
            ->route('owner.rent.index', [
                'month' => $validated['month'],
                'year'  => $validated['year'],
            ])
            ->with('success', "Rent generation complete. Generated: {$generated}, Skipped: {$skipped}.");
    }

    public function show(RentRecord $rent)
    {
        $this->authorizeRentRecord($rent);

        $rent->load(['student', 'property', 'allocations.payment']);

        return view('owner.rent.show', compact('rent'));
    }

    public function recordPayment(Request $request, RentRecord $rent)
    {
        $this->authorizeRentRecord($rent);

        $validated = $request->validate([
            'amount'                => 'required|numeric|min:0.01',
            'method'                => 'required|in:cash,upi,bank_transfer,cheque,other',
            'paid_at'               => 'nullable|date',
            'transaction_reference' => 'nullable|string|max:100',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $student = $rent->student;

        DB::transaction(function () use ($validated, $student, $rent) {
            $remainingAmount = (float) $validated['amount'];

            $payment = Payment::create([
                'student_id'            => $student->id,
                'property_id'           => $student->property_id,
                'student_name'          => $student->name,
                'amount'                => $validated['amount'],
                'method'                => $validated['method'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'receipt_number'        => 'LM-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'notes'                 => $validated['notes'] ?? null,
                'status'                => 'completed',
                'paid_at'               => !empty($validated['paid_at']) ? \Carbon\Carbon::parse($validated['paid_at']) : now(),
            ]);

            // Allocate to the target rent record first, then any other oldest unpaid/partial records
            $records = collect([$rent])->merge(
                RentRecord::where('student_id', $student->id)
                    ->where('id', '!=', $rent->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->orderBy('year')
                    ->orderBy('month')
                    ->get()
            );

            foreach ($records as $record) {
                if ($remainingAmount <= 0) {
                    break;
                }
                if ((float) $record->outstanding <= 0) {
                    continue;
                }

                $allocated = min($remainingAmount, (float) $record->outstanding);

                PaymentAllocation::create([
                    'payment_id'     => $payment->id,
                    'rent_record_id' => $record->id,
                    'amount'         => $allocated,
                ]);

                $newPaid        = (float) $record->paid_amount + $allocated;
                $newOutstanding = max(0, (float) $record->total_amount - $newPaid);

                $record->update([
                    'paid_amount' => $newPaid,
                    'outstanding' => $newOutstanding,
                    'status'      => $newOutstanding <= 0 ? 'paid' : 'partial',
                ]);

                $remainingAmount -= $allocated;
            }
        });

        return redirect()
            ->route('owner.rent.show', $rent)
            ->with('success', "Payment of ₹{$validated['amount']} recorded successfully.");
    }

    private function ownerPropertyIds(): array
    {
        return Property::where('owner_id', Auth::id())->pluck('id')->toArray();
    }

    private function authorizeRentRecord(RentRecord $rentRecord): void
    {
        if (!in_array($rentRecord->property_id, $this->ownerPropertyIds())) {
            abort(403, 'Unauthorized access to this rent record.');
        }
    }
}
