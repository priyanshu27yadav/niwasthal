@extends('layouts.student')

@section('title', 'Payments & Receipts — Niwasthal')
@section('page-title', 'Online Payments & History')

@section('content')
@if($totalOutstanding > 0)
<div class="lm-card p-4 mb-4 border-primary border-start border-4" x-data="razorpayPay()">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Pending Rent Bills</h5>
            <p class="text-muted small mb-0">
                Total Due: <strong class="text-danger fs-5">₹{{ number_format($totalOutstanding, 2) }}</strong>
                @if(!$razorpayConfigured)
                    <span class="ms-1">(Test Mode — configure Razorpay keys in <code>.env</code> for live payments)</span>
                @else
                    (Secured by Razorpay)
                @endif
            </p>
        </div>

        @if($razorpayConfigured)
            <button type="button" @click="initiatePayment({{ (float) $totalOutstanding }}, null, 'Total Outstanding Rent')" :disabled="loading" class="btn btn-primary fw-semibold px-4">
                <span x-show="loading && !selectedRecordId" class="spinner-border spinner-border-sm me-1"></span>
                <i class="bi bi-shield-lock me-1"></i> Pay All (₹{{ number_format($totalOutstanding, 2) }})
            </button>
        @else
            <form action="{{ route('student.payments.pay-rent') }}" method="POST"
                  onsubmit="return confirm('Complete test payment for all pending rent bills (₹{{ number_format($totalOutstanding, 2) }})?')">
                @csrf
                <input type="hidden" name="amount" value="{{ $totalOutstanding }}">
                <button type="submit" class="btn btn-primary fw-semibold px-4">
                    <i class="bi bi-shield-lock me-1"></i> Pay All Due (₹{{ number_format($totalOutstanding, 2) }})
                </button>
            </form>
        @endif
    </div>

    <!-- Per-Month Pay Now List -->
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Month / Year</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($outstandingRecords as $rec)
                <tr>
                    <td class="fw-semibold">{{ $rec->month_year }}</td>
                    <td>₹{{ number_format($rec->total_amount, 2) }}</td>
                    <td class="text-success">₹{{ number_format($rec->paid_amount, 2) }}</td>
                    <td class="text-danger fw-bold">₹{{ number_format($rec->outstanding, 2) }}</td>
                    <td><span class="badge bg-{{ $rec->status === 'partial' ? 'warning text-dark' : 'danger' }}">{{ ucfirst($rec->status) }}</span></td>
                    <td class="text-end">
                        @if($razorpayConfigured)
                            <button type="button" @click="initiatePayment({{ (float) $rec->outstanding }}, {{ $rec->id }}, '{{ $rec->month_year }}')"
                                    :disabled="loading" class="btn btn-sm btn-primary fw-semibold">
                                Pay Now (₹{{ number_format($rec->outstanding, 0) }})
                            </button>
                        @else
                            <form action="{{ route('student.payments.pay-rent') }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Complete test payment of ₹{{ number_format($rec->outstanding, 2) }} for {{ $rec->month_year }}?')">
                                @csrf
                                <input type="hidden" name="rent_record_id" value="{{ $rec->id }}">
                                <input type="hidden" name="amount" value="{{ $rec->outstanding }}">
                                <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                                    Pay Now (₹{{ number_format($rec->outstanding, 0) }})
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div x-show="error" class="alert alert-danger small mt-3 mb-0" x-text="error" style="display: none;"></div>
</div>
@endif

<div class="lm-card overflow-hidden">
    <div class="p-3 border-bottom"><h6 class="fw-bold mb-0">Payment History</h6></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>Receipt No</th><th>Date &amp; Time</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td class="font-monospace small fw-semibold">{{ $payment->receipt_number }}</td>
                    <td class="small text-muted">{{ $payment->paid_at?->format('d M Y, h:i A') }}</td>
                    <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                    <td><span class="badge bg-light text-dark border text-uppercase">{{ $payment->method }}</span></td>
                    <td><span class="badge bg-success-subtle text-success">{{ ucfirst($payment->status) }}</span></td>
                    <td>
                        <a href="{{ route('student.payments.receipt', $payment) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-printer me-1"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function razorpayPay() {
    return {
        loading: false,
        selectedRecordId: null,
        error: '',
        csrf: document.querySelector('meta[name="csrf-token"]').content,

        async initiatePayment(amount, rentRecordId, label) {
            this.loading = true;
            this.selectedRecordId = rentRecordId;
            this.error = '';
            try {
                const res = await fetch('{{ route("student.payments.order") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ amount: amount, rent_record_id: rentRecordId })
                });
                const order = await res.json();
                if (!res.ok || !order.success) {
                    this.error = order.message || 'Unable to create Razorpay order. Check RAZORPAY_KEY_ID / SECRET in .env.';
                    this.loading = false;
                    return;
                }

                const options = {
                    key: order.key || order.key_id,
                    amount: order.amount,
                    currency: order.currency || 'INR',
                    name: 'Niwasthal',
                    description: label || 'Rent Payment',
                    order_id: order.order_id,
                    prefill: order.prefill || {},
                    handler: async (response) => {
                        const verifyRes = await fetch('{{ route("student.payments.verify") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                            body: JSON.stringify({
                                amount: amount,
                                rent_record_id: rentRecordId,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_signature: response.razorpay_signature
                            })
                        });
                        const verifyData = await verifyRes.json();
                        if (verifyData.success) {
                            window.location.href = verifyData.receipt_url || '{{ route("student.payments.index") }}';
                        } else {
                            this.error = verifyData.message || 'Verification failed.';
                            this.loading = false;
                        }
                    },
                    modal: { ondismiss: () => { this.loading = false; } }
                };
                new Razorpay(options).open();
            } catch (e) {
                this.error = 'Network error initiating payment.';
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
