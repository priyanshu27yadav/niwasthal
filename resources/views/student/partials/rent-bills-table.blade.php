<div class="lm-card overflow-hidden" x-data="rentMonthPay()">
    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Monthly Rent Bills</h6>
            <div class="small text-muted">
                @if(!$razorpayConfigured)
                    Test / Sandbox payment mode active (configure <code>RAZORPAY_KEY_ID</code> &amp; <code>RAZORPAY_KEY_SECRET</code> in <code>.env</code> for live Razorpay).
                @else
                    Pay any pending month online securely via Razorpay.
                @endif
            </div>
        </div>
        @if(($totalOutstanding ?? 0) > 0)
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-6">
                Total Due: ₹{{ number_format($totalOutstanding, 2) }}
            </span>
        @endif
    </div>

    <div x-show="error" class="alert alert-danger m-3 mb-0 small" x-text="error" style="display: none;"></div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Month / Year</th>
                    <th>Rent</th>
                    <th>Late Fee</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Due</th>
                    <th>Status &amp; Paid Date/Time</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                @php
                    $latestPayment = $rec->latest_payment;
                    $paidTimestamp = $latestPayment?->paid_at ?? ($rec->status === 'paid' ? $rec->updated_at : null);
                @endphp
                <tr>
                    <td class="fw-bold text-dark">{{ $rec->month_year }}</td>
                    <td>₹{{ number_format($rec->rent_amount, 2) }}</td>
                    <td>₹{{ number_format($rec->late_fee, 2) }}</td>
                    <td class="fw-semibold">₹{{ number_format($rec->total_amount, 2) }}</td>
                    <td class="text-success fw-semibold">₹{{ number_format($rec->paid_amount, 2) }}</td>
                    <td class="fw-bold {{ $rec->outstanding > 0 ? 'text-danger' : 'text-muted' }}">
                        ₹{{ number_format($rec->outstanding, 2) }}
                    </td>
                    <td>
                        <span class="badge bg-{{ $rec->status === 'paid' ? 'success' : ($rec->status === 'partial' ? 'warning text-dark' : 'danger') }}">
                            {{ ucfirst($rec->status) }}
                        </span>
                        @if($paidTimestamp)
                            <div class="small text-muted mt-1">
                                <i class="bi bi-clock-history me-1"></i>{{ $paidTimestamp->format('d M Y, h:i A') }}
                            </div>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex align-items-center gap-1 flex-wrap justify-content-end">
                            @if($rec->outstanding > 0)
                                @if($razorpayConfigured)
                                    <button type="button"
                                            @click="payMonthRazorpay({{ (float) $rec->outstanding }}, {{ $rec->id }}, '{{ $rec->month_year }}')"
                                            :disabled="loadingId === {{ $rec->id }}"
                                            class="btn btn-sm btn-primary fw-semibold">
                                        <span x-show="loadingId === {{ $rec->id }}" class="spinner-border spinner-border-sm me-1"></span>
                                        <i class="bi bi-credit-card me-1"></i> Pay Now (₹{{ number_format($rec->outstanding, 0) }})
                                    </button>
                                @else
                                    <form action="{{ route('student.payments.pay-rent') }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Complete test payment of ₹{{ number_format($rec->outstanding, 2) }} for {{ $rec->month_year }}?')">
                                        @csrf
                                        <input type="hidden" name="rent_record_id" value="{{ $rec->id }}">
                                        <input type="hidden" name="amount" value="{{ $rec->outstanding }}">
                                        <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                                            <i class="bi bi-credit-card me-1"></i> Pay Now (₹{{ number_format($rec->outstanding, 0) }})
                                        </button>
                                    </form>
                                @endif
                            @elseif($latestPayment)
                                <a href="{{ route('student.payments.receipt', $latestPayment) }}" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-receipt me-1"></i> Receipt
                                </a>
                            @endif
                            <a href="{{ route('student.rent.show', $rec) }}" class="btn btn-sm btn-light border">Details</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No rent bills generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($razorpayConfigured)
@once
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function rentMonthPay() {
    return {
        loadingId: null,
        error: '',
        csrf: document.querySelector('meta[name="csrf-token"]').content,

        async payMonthRazorpay(amount, rentRecordId, label) {
            this.loadingId = rentRecordId;
            this.error = '';
            try {
                const res = await fetch('{{ route("student.payments.order") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ amount: amount, rent_record_id: rentRecordId })
                });
                const order = await res.json();
                if (!res.ok || !order.success) {
                    this.error = order.message || 'Unable to create Razorpay order.';
                    this.loadingId = null;
                    return;
                }

                const options = {
                    key: order.key || order.key_id,
                    amount: order.amount,
                    currency: order.currency || 'INR',
                    name: 'Niwasthal',
                    description: label + ' Rent Payment',
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
                            window.location.reload();
                        } else {
                            this.error = verifyData.message || 'Payment verification failed.';
                            this.loadingId = null;
                        }
                    },
                    modal: { ondismiss: () => { this.loadingId = null; } }
                };
                new Razorpay(options).open();
            } catch (e) {
                this.error = 'Network error initiating Razorpay payment.';
                this.loadingId = null;
            }
        }
    };
}
</script>
@endpush
@endonce
@else
@once
@push('scripts')
<script>
function rentMonthPay() {
    return { loadingId: null, error: '' };
}
</script>
@endpush
@endonce
@endif
