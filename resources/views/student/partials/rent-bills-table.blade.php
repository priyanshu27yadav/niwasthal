@php
    $owner = $student->property?->owner;
@endphp

<div class="lm-card overflow-hidden" x-data="rentMonthPay()">
    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Monthly Rent Bills</h6>
            <div class="small text-muted">
                Pay your monthly rent directly to the property owner via UPI, QR code scan, or bank transfer.
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
                                <button type="button"
                                        @click="openDirectPayModal({{ (float) $rec->outstanding }}, {{ $rec->id }}, '{{ $rec->month_year }}')"
                                        class="btn btn-sm btn-primary fw-semibold">
                                    <i class="bi bi-wallet2 me-1"></i> Pay Now (₹{{ number_format($rec->outstanding, 0) }})
                                </button>
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

    <!-- Direct Payment Modal with Owner Bank, UPI & QR Code -->
    <div class="modal fade" id="directPayModal" tabindex="-1" aria-labelledby="directPayModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title fw-bold text-dark" id="directPayModalLabel">
                            <i class="bi bi-shield-check text-primary me-2"></i>Pay Rent to Owner
                        </h5>
                        <p class="text-muted small mb-0">
                            Month: <strong x-text="targetLabel"></strong> &bull; Due Amount: <strong class="text-success fs-6">₹<span x-text="Number(targetAmount).toLocaleString('en-IN')"></span></strong>
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Owner Payment Information Tabs -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Step 1: Choose Owner Payment Receiving Option</label>
                        <ul class="nav nav-pills nav-fill bg-light p-1.5 rounded-3 mb-3" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active py-2 small fw-semibold" id="qr-tab" data-bs-toggle="pill" data-bs-target="#pay-qr" type="button" role="tab">
                                    <i class="bi bi-qr-code-scan me-1"></i> Scan QR Code
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 small fw-semibold" id="upi-tab" data-bs-toggle="pill" data-bs-target="#pay-upi" type="button" role="tab">
                                    <i class="bi bi-upc-scan me-1"></i> UPI ID / VPA
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-2 small fw-semibold" id="bank-tab" data-bs-toggle="pill" data-bs-target="#pay-bank" type="button" role="tab">
                                    <i class="bi bi-bank2 me-1"></i> Bank Account
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content border rounded-3 p-3 bg-white">
                            <!-- QR Code Tab -->
                            <div class="tab-pane fade show active text-center" id="pay-qr" role="tabpanel">
                                @if($owner && $owner->upi_qr_image)
                                    <div class="mb-2">
                                        <img src="{{ asset($owner->upi_qr_image) }}" alt="Owner QR Code" class="img-fluid rounded border shadow-sm p-1" style="max-height: 220px;">
                                    </div>
                                    <p class="small text-muted mb-0">Scan this QR code using Google Pay, PhonePe, Paytm, or any UPI app to pay.</p>
                                @elseif($owner && $owner->upi_id)
                                    <div class="mb-2">
                                        <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent('upi://pay?pa={{ $owner->upi_id }}&pn={{ urlencode($owner->name) }}&am=' + targetAmount + '&cu=INR&tn=Rent_' + encodeURIComponent(targetLabel))"
                                             alt="UPI QR Code" class="img-fluid rounded border shadow-sm p-1" style="max-height: 200px;">
                                    </div>
                                    <p class="small text-muted mb-0">Scan directly to pay ₹<span x-text="targetAmount"></span> to <strong>{{ $owner->upi_id }}</strong></p>
                                @else
                                    <div class="py-4 text-muted">
                                        <i class="bi bi-qr-code fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        <p class="small mb-1">Owner has not uploaded a custom QR code image yet.</p>
                                        <p class="small text-muted mb-0">Please use the <strong>UPI ID</strong> or <strong>Bank Account</strong> tab above, or pay in cash.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- UPI ID Tab -->
                            <div class="tab-pane fade" id="pay-upi" role="tabpanel">
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
                                            <i class="bi bi-phone me-1"></i> Open in Installed UPI App (GPay/PhonePe)
                                        </a>
                                    </div>
                                @else
                                    <div class="py-4 text-center text-muted">
                                        <p class="small mb-0">Owner has not registered a UPI ID yet. Please check the Bank Account tab or contact property manager.</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Bank Account Tab -->
                            <div class="tab-pane fade" id="pay-bank" role="tabpanel">
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
                                        <p class="small mb-0">Bank account details not registered yet. Please check the UPI tab or contact the owner.</p>
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

@push('scripts')
<script>
function rentMonthPay() {
    return {
        targetAmount: 0,
        targetRecordId: null,
        targetLabel: '',
        selectedMethod: 'qr_code',
        copiedUpi: false,
        error: '',

        openDirectPayModal(amount, rentRecordId, label) {
            this.targetAmount = amount;
            this.targetRecordId = rentRecordId;
            this.targetLabel = label;
            this.copiedUpi = false;

            const modalEl = document.getElementById('directPayModal');
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