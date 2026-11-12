<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\RentRecord;
use App\Models\Student;
use App\Services\RazorpayService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly RazorpayService $razorpay,
        private readonly WhatsAppService $whatsapp
    ) {}

    /**
     * Display the authenticated student's payment history.
     */
    public function index(Request $request): View
    {
        $student = $this->getStudent();

        $payments = Payment::with(['allocations.rentRecord'])
            ->where('student_id', $student->id)
            ->latest('paid_at')
            ->paginate(15)
            ->withQueryString();

        // Outstanding rent records
        $outstandingRecords = RentRecord::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $totalOutstanding = $outstandingRecords->sum('outstanding');
        $razorpayConfigured = $this->razorpay->isConfigured();

        return view('student.payments.index', compact(
            'payments',
            'outstandingRecords',
            'totalOutstanding',
            'razorpayConfigured'
        ));
    }

    /**
     * Create a Razorpay order for the student's rent.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $student = $this->getStudent();

        $validated = $request->validate([
            'amount'         => ['required', 'numeric', 'min:1'],
            'rent_record_id' => ['nullable', 'integer'],
        ]);

        $rentRecordId = isset($validated['rent_record_id']) ? (int) $validated['rent_record_id'] : null;
        if ($rentRecordId) {
            RentRecord::where('id', $rentRecordId)->where('student_id', $student->id)->firstOrFail();
        }

        $amount = (float) $validated['amount'];

        // Sanity check: amount should not exceed outstanding balance
        $outstanding = (float) $student->outstanding_rent;
        if ($amount > $outstanding + 0.01) {
            return response()->json([
                'success' => false,
                'message' => "Payment amount (₹{$amount}) exceeds outstanding balance (₹{$outstanding}).",
            ], 422);
        }

        try {
            $receiptId = 'RCP-' . $student->id . '-' . time();

            $orderData = $this->razorpay->createOrder($amount, $receiptId, [
                'student_id'     => $student->id,
                'student_name'   => $student->name,
                'property_id'    => $student->property_id,
                'rent_record_id' => $rentRecordId,
            ]);

            return response()->json([
                'success'        => true,
                'order_id'       => $orderData['order_id'],
                'amount'         => $orderData['amount'],          // paise
                'currency'       => $orderData['currency'],
                'key'            => config('services.razorpay.key_id'),
                'key_id'         => config('services.razorpay.key_id'),
                'rent_record_id' => $rentRecordId,
                'student_name'   => $student->name,
                'student_email'  => $student->email ?? $student->user->email ?? '',
                'student_mobile' => $student->mobile,
                'prefill'        => [
                    'name'    => $student->name,
                    'email'   => $student->email ?? $student->user->email ?? '',
                    'contact' => $student->mobile,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Student PaymentController@createOrder failed.', [
                'student_id' => $student->id,
                'amount'     => $amount,
                'exception'  => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to create payment order. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify the Razorpay payment signature, record the payment, allocate it to
     * outstanding rent records, and send a WhatsApp confirmation.
     *
     * Expected POST body (application/json or form):
     *   razorpay_order_id, razorpay_payment_id, razorpay_signature
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $student = $this->getStudent();

        $validated = $request->validate([
            'razorpay_order_id'   => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature'  => ['required', 'string'],
            'amount'              => ['required', 'numeric', 'min:1'],
            'rent_record_id'      => ['nullable', 'integer'],
        ]);

        // 1. Verify signature
        $isValid = $this->razorpay->verifyPayment(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        if (!$isValid) {
            Log::warning('Student PaymentController@verifyPayment: Invalid signature.', [
                'student_id' => $student->id,
                'order_id'   => $validated['razorpay_order_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed. Please contact support.',
            ], 422);
        }

        // 2. Guard against duplicate payment processing
        $duplicate = Payment::where('razorpay_payment_id', $validated['razorpay_payment_id'])->first();
        if ($duplicate) {
            return response()->json([
                'success'     => true,
                'message'     => 'Payment already recorded.',
                'payment_id'  => $duplicate->id,
                'receipt_number' => $duplicate->receipt_number,
                'receipt_url' => route('student.payments.receipt', $duplicate),
            ]);
        }

        try {
            DB::beginTransaction();

            $amountInRupees = (float) $validated['amount'];

            // 3. Create the Payment record
            $payment = Payment::create([
                'student_id'          => $student->id,
                'property_id'         => $student->property_id,
                'student_name'        => $student->name,
                'amount'              => $amountInRupees,
                'method'              => 'razorpay',
                'razorpay_order_id'   => $validated['razorpay_order_id'],
                'razorpay_payment_id' => $validated['razorpay_payment_id'],
                'razorpay_signature'  => $validated['razorpay_signature'],
                'receipt_number'      => 'TMP-' . uniqid('', true),
                'status'              => 'completed',
                'paid_at'             => now(),
            ]);

            // 4. Set receipt number
            $payment->receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT);
            $payment->save();

            // 5. Allocate payment to rent records (requested month first, then oldest first)
            $rentRecordId = isset($validated['rent_record_id']) ? (int) $validated['rent_record_id'] : null;
            $this->allocatePayment($payment, $student, $rentRecordId);

            DB::commit();

            // 6. Send WhatsApp confirmation (non-blocking — failure won't break payment)
            try {
                $this->whatsapp->sendPaymentConfirmation(
                    $student->mobile,
                    $student->name,
                    $amountInRupees,
                    $payment->receipt_number
                );
            } catch (\Throwable $e) {
                Log::warning('Student PaymentController@verifyPayment: WhatsApp notification failed.', [
                    'payment_id' => $payment->id,
                    'exception'  => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success'        => true,
                'message'        => 'Payment verified and recorded successfully.',
                'payment_id'     => $payment->id,
                'receipt_number' => $payment->receipt_number,
                'receipt_url'    => route('student.payments.receipt', $payment),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Student PaymentController@verifyPayment: DB transaction failed.', [
                'student_id'  => $student->id,
                'payment_id'  => $validated['razorpay_payment_id'],
                'exception'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment was verified but could not be recorded. Please contact support with payment ID: ' . $validated['razorpay_payment_id'],
            ], 500);
        }
    }

    /**
     * Record a local/sandbox rent payment when live Razorpay credentials are not configured yet.
     */
    public function payMonth(Request $request)
    {
        if ($this->razorpay->isConfigured() || (!app()->environment(['local', 'testing']) && !config('app.debug'))) {
            abort(403, 'Direct sandbox payment is disabled when live Razorpay gateway is active.');
        }

        $student = $this->getStudent();

        $validated = $request->validate([
            'rent_record_id' => ['nullable', 'integer'],
            'amount'         => ['nullable', 'numeric', 'min:1'],
        ]);

        $rentRecord = null;
        if (!empty($validated['rent_record_id'])) {
            $rentRecord = RentRecord::where('id', (int) $validated['rent_record_id'])
                ->where('student_id', $student->id)
                ->firstOrFail();

            if ((float) $rentRecord->outstanding <= 0) {
                return back()->with('error', $rentRecord->month_year . ' rent is already paid.');
            }
        }

        $maxPayable = $rentRecord ? (float) $rentRecord->outstanding : (float) $student->outstanding_rent;
        if ($maxPayable <= 0) {
            return back()->with('error', 'No outstanding dues to pay.');
        }

        $amount = isset($validated['amount']) ? min((float) $validated['amount'], $maxPayable) : $maxPayable;

        $payment = DB::transaction(function () use ($student, $rentRecord, $amount) {
            $payment = Payment::create([
                'student_id'     => $student->id,
                'property_id'    => $student->property_id,
                'student_name'   => $student->name,
                'amount'         => $amount,
                'method'         => 'upi',
                'receipt_number' => 'TMP-' . uniqid('', true),
                'status'         => 'completed',
                'paid_at'        => now(),
                'notes'          => $rentRecord
                    ? ('Sandbox rent payment for ' . $rentRecord->month_year)
                    : 'Sandbox rent payment',
            ]);

            $payment->receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT);
            $payment->save();

            $this->allocatePayment($payment, $student, $rentRecord?->id);

            return $payment;
        });

        $label = $rentRecord ? $rentRecord->month_year : 'Outstanding Rent';

        return back()->with('success', "Payment of ₹" . number_format($amount, 2) . " for {$label} completed on " . $payment->paid_at->format('d M Y, h:i A') . " (Receipt: {$payment->receipt_number}).");
    }

    /**
     * Display the payment receipt for a specific payment.
     * Students can only view their own receipts.
     */
    public function receipt(Payment $payment): View
    {
        $student = $this->getStudent();

        // Ensure this receipt belongs to the authenticated student
        if ($payment->student_id !== $student->id) {
            abort(403, 'Unauthorized action.');
        }

        $payment->load(['property', 'allocations.rentRecord']);

        return view('student.payments.receipt', compact('payment', 'student'));
    }

    /**
     * Handle Razorpay webhook callbacks (payment.captured / payment.failed).
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature', '');
        $payload   = $request->getContent();
        $secret    = config('services.razorpay.webhook_secret', '');

        if ($secret) {
            $expected = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($expected, $signature)) {
                return response()->json(['status' => 'invalid signature'], 400);
            }
        }

        $event = $request->input('event');
        $entity = $request->input('payload.payment.entity', []);
        $razorpayPaymentId = $entity['id'] ?? null;

        if ($event === 'payment.failed' && $razorpayPaymentId) {
            Payment::where('razorpay_payment_id', $razorpayPaymentId)
                ->update(['status' => 'failed']);
        }

        return response()->json(['status' => 'ok']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Get the Student record for the currently authenticated user.
     * Aborts with 403 if no student profile is linked.
     */
    private function getStudent(): Student
    {
        $student = Auth::user()->student;

        if (!$student) {
            abort(403, 'No student profile linked to your account.');
        }

        return $student;
    }

    /**
     * Allocate the given payment amount to the student's unpaid/partial rent
     * records (target month first if provided, otherwise oldest month first).
     */
    private function allocatePayment(Payment $payment, Student $student, ?int $targetRentRecordId = null): void
    {
        $remaining = (float) $payment->amount;

        $rentRecords = RentRecord::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('outstanding', '>', 0)
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->sortBy(fn (RentRecord $r) => [
                $targetRentRecordId && $r->id === $targetRentRecordId ? 0 : 1,
                $r->year,
                $r->month,
            ]);

        foreach ($rentRecords as $record) {
            if ($remaining <= 0) {
                break;
            }

            $allocate = min($remaining, (float) $record->outstanding);

            PaymentAllocation::create([
                'payment_id'     => $payment->id,
                'rent_record_id' => $record->id,
                'amount'         => $allocate,
            ]);

            $newPaid        = (float) $record->paid_amount + $allocate;
            $newOutstanding = (float) $record->outstanding - $allocate;

            $record->update([
                'paid_amount' => $newPaid,
                'outstanding' => $newOutstanding,
                'status'      => $newOutstanding <= 0 ? 'paid' : 'partial',
            ]);

            $remaining -= $allocate;
        }
    }
}
