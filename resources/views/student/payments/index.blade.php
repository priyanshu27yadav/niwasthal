@extends('layouts.student')

@section('title', 'Payments & Receipts — Niwasthal')
@section('page-title', 'Online Payments & History')

@php
    $owner = $student->property?->owner;
@endphp

@section('content')
@if($totalOutstanding > 0)
<div class="lm-card p-4 mb-4 border-primary border-start border-4" x-data="studentPay()">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Pending Rent Bills</h5>
            <p class="text-muted small mb-0">
                Total Due: <strong class="text-danger fs-5">₹{{ number_format($totalOutstanding, 2) }}</strong>
                &bull; Pay directly to owner via UPI, QR Code scan, or Bank Transfer
            </p>
        </div>

        <button type="button" @click="openDirectModal({{ (float) $totalOutstanding }}, null, 'Total Outstanding Balance')" class="btn btn-primary fw-semibold px-4">
            <i class="bi bi-wallet2 me-1"></i> Pay All Due (₹{{ number_format($totalOutstanding, 2) }})
        </button>
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
                        <button type="button" @click="openDirectModal({{ (float) $rec->outstanding }}, {{ $rec->id }}, '{{ $rec->month_year }}')"
                                class="btn btn-sm btn-primary fw-semibold">
                            <i class="bi bi-credit-card me-1"></i> Pay Now (₹{{ number_format($rec->outstanding, 0) }})
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Direct Payment Modal with Owner Bank, UPI & QR Code -->
    <div class="modal fade" id="studentDirectPayModal" tabindex="-1" aria-labelledby="studentDirectPayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="studentDirectPayModalLabel">
                            <i class="bi bi-shield-check text-primary me-2"></i>Pay Rent to Property Owner
                        </h5>
                        <p class="text-muted small mb-0">
                            Month: <strong x-text="targetLabel"></strong> &bull; Amount: <strong class="text-success fs-6">₹<span x-text="Number(targetAmount).toLocaleString('en-IN')"></span></strong>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Owner Receiving Options Tabs -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Step 1: Choose Owner Payment Receiving Option</label>
                        <ul class="nav nav-pills nav-fill bg-light p-1.5 rounded-3 mb-3" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active py-2 small fw-semibold" id="s-qr-tab" data-bs-toggle="pill" data-bs-target="#s-pay-qr" type="button" role="tab">
                                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR Code
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 small fw-semibold" id="s-upi-tab" data-bs-toggle="pill" data-bs-target="#s-pay-upi" type="button" role="tab">
                                    <i class="bi bi-upc-scan me-1"></i> UPI ID / VPA
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 small fw-semibold" id="s-bank-tab" data-bs-toggle="pill" data-bs-target="#s-pay-bank" type="button" role="tab">
                                    <i class="bi bi-bank2 me-1"></i> Bank Account
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content border rounded-3 p-3 bg-white">
                            <!-- QR Code Tab -->
                            <div class="tab-pane fade show active text-center" id="s-pay-qr" role="tabpanel">
                                @if($owner && $owner->upi_qr_image)
                                    <div class="mb-2">
                                        <img src="{{ asset($owner->upi_qr_image) }}" alt="Owner QR Code" class="img-fluid rounded border shadow-sm p-1" style="max-height: 220px;">
                                    </div>
                                    <p class="small text-muted mb-0">Scan this QR code using Google Pay, PhonePe, Paytm, or BHIM to pay directly.</p>
                                @elseif($owner && $owner->upi_id)
                                    <div class="mb-2">
                                        <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent('upi://pay?pa={{ $owner->upi_id }}&pn={{ urlencode($owner->name) }}&am=' + targetAmount + '&cu=INR&tn=Rent_' + encodeURIComponent(targetLabel))"
                                             alt="UPI QR Code" class="img-fluid rounded border shadow-sm p-1" style="max-height: 200px;">
                                    </div>
                                    <p class="small text-muted mb-0">Scan directly with any UPI app to pay ₹<span x-text="targetAmount"></span> to <strong>{{ $owner->upi_id }}</strong></p>
                                @else
                                    <div class="py-4 text-muted">
                                        <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <p class="small mb-1">Owner has not uploaded a QR code poster.</p>
                                        <p class="small text-muted mb-0">Please check the UPI ID or Bank Account tab above.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- UPI ID Tab -->
                            <div class="tab-pane fade" id="s-pay-upi" role="tabpanel">
                                @if($owner && $owner->upi_id)
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                                        <div>
                                            <span class="small text-muted d-block">Owner's UPI ID:</span>
                                            <span class="fs-5 fw-bold font-monospace text-primary">{{ $owner->upi_id }}</span>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm" @click="copyUpiId('{{ $owner->upi_id }}')">
                                            <i class="bi" :class="copiedUpi ? 'bi-check-lg text-success' : 'bi-clipboard'"></i>
                                            <span x-text="copiedUpi ? 'Copied!' : 'Copy UPI ID'"></span>
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <a :href="'upi://pay?pa={{ $owner->upi_id }}&pn={{ urlencode($owner->name) }}&am=' + targetAmount + '&cu=INR&tn=Rent_' + encodeURIComponent(targetLabel)"
                                           class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-phone me-1"></i> Open in Installed UPI App
                                        </a>
                                    </div>
                                @else
                                    <div class="py-4 text-center text-muted">
                                        <p class="small mb-0">Owner has not registered a UPI ID yet.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Bank Account Tab -->
                            <div class="tab-pane fade" id="s-pay-bank" role="tabpanel">
                                @if($owner && ($owner->bank_account_number || $owner->bank_name))
                                    <div class="row g-2 small">
                                        <div class="col-sm-6 p-2 bg-light rounded">
                                            <span class="text-muted d-block">Bank Name:</span>
                                            <strong class="text-dark">{{ $owner->bank_name ?? '—' }}</strong>
                                        </div>
                                        <div class="col-sm-6 p-2 bg-light rounded">
                                            <span class="text-muted d-block">Account Holder:</span>
                                            <strong class="text-dark">{{ $owner->bank_account_holder ?? $owner->name }}</strong>
                                        </div>
                                        <div class="col-sm-6 p-2 bg-light rounded">
                                            <span class="text-muted d-block">Account Number:</span>
                                            <strong class="font-monospace text-dark fs-6">{{ $owner->bank_account_number ?? '—' }}</strong>
                                        </div>
                                        <div class="col-sm-6 p-2 bg-light rounded">
                                            <span class="text-muted d-block">IFSC Code:</span>
                                            <strong class="font-monospace text-dark fs-6">{{ $owner->bank_ifsc ?? '—' }}</strong>
                                        </div>
                                    </div>
                                @else
                                    <div class="py-4 text-center text-muted">
                                        <p class="small mb-0">Bank account details not configured yet.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Payment Confirmation Form -->
                    <form action="{{ route('student.payments.pay-rent') }}" method="POST">
                        @csrf
                        <input type="hidden" name="rent_record_id" :value="targetRecordId">
                        <input type="hidden" name="amount" :value="targetAmount">

                        <div class="p-3 rounded-3 border bg-light">
                            <label class="form-label small fw-bold text-dark mb-2">Step 2: Enter Details of the Account / Method You Paid From</label>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Payment Method Used <span class="text-danger">*</span></label>
                                    <select name="payment_method_type" class="form-select form-select-sm" x-model="selectedMethod" required>
                                        <option value="qr_code">QR Code Scan</option>
                                        <option value="upi_id">UPI ID / App (GPay, PhonePe, Paytm)</option>
                                        <option value="bank_transfer">Bank Transfer (NEFT / IMPS / NetBanking)</option>
                                        <option value="cash">Cash Paid to Owner / Caretaker</option>
                                    </select>
                                </div>

                                <div class="col-md-6" x-show="selectedMethod === 'upi_id' || selectedMethod === 'qr_code'">
                                    <label class="form-label small fw-semibold">Your UPI ID (Who Paid) <span class="text-muted">(Optional)</span></label>
                                    <input type="text" name="payer_upi_id" class="form-control form-control-sm" placeholder="e.g. yourname@okhdfcbank">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Payer Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="payer_phone" value="{{ $student->mobile }}" class="form-control form-control-sm" required>
                                    <small class="text-muted" style="font-size: 0.72rem;">Phone number linked to the paying account.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">UTR / Transaction Reference No. <span class="text-muted">(Optional)</span></label>
                                    <input type="text" name="transaction_reference" class="form-control form-control-sm" placeholder="e.g. 12-digit UTR from your bank app">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success fw-semibold px-4">
                                <i class="bi bi-check-circle-fill me-1"></i> Confirm &amp; Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Complete Payment History Table -->
<div class="lm-card overflow-hidden">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Payment History</h6>
        <span class="small text-muted">All payments recorded under your tenancy</span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th>Receipt No</th>
                    <th>Date &amp; Time</th>
                    <th>Amount Paid</th>
                    <th>Payment Method</th>
                    <th>Paid From (Your Info)</th>
                    <th>Received In (Account)</th>
                    <th>Status</th>
                    <th class="text-end">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td class="font-monospace small fw-bold text-primary">{{ $payment->receipt_number }}</td>
                    <td class="small text-muted">
                        <div>{{ $payment->paid_at?->format('d M Y') }}</div>
                        <div style="font-size: 0.73rem;">{{ $payment->paid_at?->format('h:i A') }}</div>
                    </td>
                    <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                    <td>
                        @php
                            $methodType = $payment->payment_method_type ?? $payment->method;
                        @endphp
                        @if($methodType === 'qr_code')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1">
                                <i class="bi bi-qr-code-scan"></i> QR Scan
                            </span>
                        @elseif($methodType === 'upi_id' || $methodType === 'upi')
                            <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle d-inline-flex align-items-center gap-1" style="background-color: #e0e7ff; color: #4338ca;">
                                <i class="bi bi-upc-scan"></i> UPI ID
                            </span>
                        @elseif($methodType === 'bank_transfer')
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle d-inline-flex align-items-center gap-1">
                                <i class="bi bi-bank2"></i> Bank Transfer
                            </span>
                        @elseif($methodType === 'cash')
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle d-inline-flex align-items-center gap-1">
                                <i class="bi bi-cash-stack"></i> Cash
                            </span>
                        @else
                            <span class="badge bg-light text-dark border text-uppercase">{{ $payment->method }}</span>
                        @endif
                    </td>
                    <td>
                        @if(!empty($payment->payer_upi_id))
                            <div class="small font-monospace text-dark"><i class="bi bi-at text-primary"></i> {{ $payment->payer_upi_id }}</div>
                        @endif
                        @if(!empty($payment->payer_phone))
                            <div class="small text-muted"><i class="bi bi-telephone text-secondary"></i> {{ $payment->payer_phone }}</div>
                        @endif
                        @if(empty($payment->payer_upi_id) && empty($payment->payer_phone))
                            <span class="small text-muted">—</span>
                        @endif
                        @if(!empty($payment->transaction_reference))
                            <div class="text-muted" style="font-size: 0.72rem;">Ref: {{ $payment->transaction_reference }}</div>
                        @endif
                    </td>
                    <td>
                        @if(!empty($payment->received_to_account))
                            <span class="badge bg-light text-dark border small fw-normal">
                                <i class="bi bi-arrow-down-left text-success me-1"></i>{{ $payment->received_to_account }}
                            </span>
                        @else
                            <span class="small text-muted">Owner Account</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> Completed
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('student.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="bi bi-printer me-1"></i> Receipt
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection

@push('scripts')
<script>
function studentPay() {
    return {
        targetAmount: 0,
        targetRecordId: null,
        targetLabel: '',
        selectedMethod: 'qr_code',
        copiedUpi: false,

        openDirectModal(amount, rentRecordId, label) {
            this.targetAmount = amount;
            this.targetRecordId = rentRecordId;
            this.targetLabel = label;
            this.copiedUpi = false;

            const modalEl = document.getElementById('studentDirectPayModal');
            if (modalEl && window.bootstrap) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        },

        copyUpiId(text) {
            if (!text) return;
            navigator.clipboard.writeText(text).then(() => {
                this.copiedUpi = true;
                setTimeout(() => this.copiedUpi = false, 3000);
            }).catch(() => {
                prompt("Copy UPI ID:", text);
            });
        }
    };
}
</script>
@endpush