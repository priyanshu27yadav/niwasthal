<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\RentRecord;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Display a paginated list of all payments for the authenticated owner's properties.
     * Supports filtering by student, date range, and payment method.
     */
    public function index(Request $request): View
    {
        $owner = Auth::user();

        // Collect property IDs that belong to this owner
        $propertyIds = $owner->properties()->pluck('id');

        $query = Payment::with(['student.room', 'student.bed', 'property'])
            ->whereIn('property_id', $propertyIds)
            ->latest('paid_at');

        // Filter: student name or ID
        if ($request->filled('student')) {
            $search = $request->student;
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhereHas('student', fn ($s) => $s->where('mobile', 'like', "%{$search}%"));
            });
        }

        // Filter: payment method
        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        // Filter: start date
        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        // Filter: end date
        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(20)->withQueryString();

        // Summary totals for the filtered set
        $totalCollected = $query->clone()
            ->where('status', 'completed')
            ->sum('amount');

        // Students list for the filter dropdown
        $students = Student::whereIn('property_id', $propertyIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile']);

        return view('owner.payments.index', compact(
            'payments',
            'students',
            'totalCollected'
        ));
    }

    /**
     * Store a manually recorded cash or UPI payment made by a student.
     * Allocates the payment amount to the student's oldest outstanding rent records.
     */
    public function store(Request $request): RedirectResponse
    {
        $owner = Auth::user();

        $validated = $request->validate([
            'student_id'            => ['required', 'exists:students,id'],
            'amount'                => ['required', 'numeric', 'min:1'],
            'method'                => ['required', 'in:cash,upi,bank_transfer,cheque'],
            'paid_at'               => ['required', 'date', 'before_or_equal:today'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::with('property')->findOrFail($validated['student_id']);

        // Ensure this student belongs to one of the owner's properties
        if ($student->property->owner_id !== $owner->id) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            // Create the payment record
            $payment = Payment::create([
                'student_id'            => $student->id,
                'property_id'           => $student->property_id,
                'student_name'          => $student->name,
                'amount'                => $validated['amount'],
                'method'                => $validated['method'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'notes'                 => $validated['notes'] ?? null,
                'status'                => 'completed',
                'paid_at'               => $validated['paid_at'],
            ]);

            // Assign receipt number after we have the ID
            $payment->receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT);
            $payment->save();

            // Allocate payment to outstanding rent records (oldest first)
            $this->allocatePayment($payment, $student);

            DB::commit();

            return redirect()
                ->route('owner.payments.receipt', $payment)
                ->with('success', "Payment of ₹{$payment->amount} recorded successfully. Receipt: {$payment->receipt_number}");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Owner PaymentController@store failed.', [
                'exception'  => $e->getMessage(),
                'student_id' => $validated['student_id'],
                'amount'     => $validated['amount'],
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to record payment. Please try again.']);
        }
    }

    /**
     * Display the payment receipt for the given payment.
     */
    public function receipt(Payment $payment): View
    {
        $owner = Auth::user();

        // Ensure the payment belongs to one of this owner's properties
        $propertyIds = $owner->properties()->pluck('id');
        if (!$propertyIds->contains($payment->property_id)) {
            abort(403, 'Unauthorized action.');
        }

        $payment->load(['student', 'property', 'allocations.rentRecord']);

        return view('owner.payments.receipt', compact('payment'));
    }

    /**
     * Delete a payment only if it is still in "pending" status.
     * Completed or cancelled payments cannot be deleted.
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $owner = Auth::user();

        // Ensure this payment belongs to one of the owner's properties
        $propertyIds = $owner->properties()->pluck('id');
        if (!$propertyIds->contains($payment->property_id)) {
            abort(403, 'Unauthorized action.');
        }

        if ($payment->status !== 'pending') {
            return back()->withErrors([
                'error' => 'Only pending payments can be deleted.',
            ]);
        }

        try {
            DB::beginTransaction();

            // Remove any allocations first (referential integrity)
            $payment->allocations()->delete();

            $payment->delete();

            DB::commit();

            return redirect()
                ->route('owner.payments.index')
                ->with('success', 'Pending payment deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Owner PaymentController@destroy failed.', [
                'exception'  => $e->getMessage(),
                'payment_id' => $payment->id,
            ]);

            return back()->withErrors(['error' => 'Failed to delete payment. Please try again.']);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Allocate the given payment amount to the student's unpaid/partial rent
     * records in chronological order (oldest month first).
     */
    private function allocatePayment(Payment $payment, Student $student): void
    {
        $remaining = (float) $payment->amount;

        // Fetch unpaid or partially paid rent records, oldest first
        $rentRecords = RentRecord::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('outstanding', '>', 0)
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        foreach ($rentRecords as $record) {
            if ($remaining <= 0) {
                break;
            }

            $allocate = min($remaining, (float) $record->outstanding);

            // Create allocation
            PaymentAllocation::create([
                'payment_id'     => $payment->id,
                'rent_record_id' => $record->id,
                'amount'         => $allocate,
            ]);

            // Update rent record
            $newPaid        = (float) $record->paid_amount + $allocate;
            $newOutstanding = (float) $record->outstanding - $allocate;

            $record->update([
                'paid_amount'  => $newPaid,
                'outstanding'  => $newOutstanding,
                'status'       => $newOutstanding <= 0 ? 'paid' : 'partial',
            ]);

            $remaining -= $allocate;
        }
    }
}
