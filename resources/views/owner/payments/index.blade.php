@extends('layouts.owner')

@section('title', 'Transactions — Niwasthal')
@section('page-title', 'Transactions & Payment History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-arrow-left-right text-primary me-2"></i>Transactions</h4>
        <p class="text-muted small mb-0">Complete record of rent, security deposits, and dues received from renters.</p>
    </div>
    @if(isset($totalCollected))
    <div class="lm-card px-3 py-2 bg-white d-flex align-items-center gap-2 border">
        <span class="text-muted small">Total Collected:</span>
        <span class="text-success fw-bold fs-6">₹{{ number_format($totalCollected, 2) }}</span>
    </div>
    @endif
</div>

<!-- Filters Card -->
<div class="lm-card p-3 mb-4">
    <form action="{{ route('owner.payments.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3 col-sm-6">
            <input type="text" name="student" value="{{ request('student') }}" class="form-control form-control-sm" placeholder="Search by renter name/mobile...">
        </div>
        <div class="col-md-2 col-sm-6">
            <select name="method" class="form-select form-select-sm">
                <option value="">All Methods</option>
                <option value="upi" {{ request('method') === 'upi' ? 'selected' : '' }}>UPI / QR Code</option>
                <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-4">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" placeholder="From date">
        </div>
        <div class="col-md-2 col-sm-4">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" placeholder="To date">
        </div>
        <div class="col-md-3 col-sm-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
            <a href="{{ route('owner.payments.index') }}" class="btn btn-light btn-sm border">Reset</a>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr class="small text-muted text-uppercase">
                    <th>Date & Time</th>
                    <th>Receipt #</th>
                    <th>Renter & Room</th>
                    <th>Amount Paid</th>
                    <th>Payment Method</th>
                    <th>Paid From (Payer Info)</th>
                    <th>Received In (Account)</th>
                    <th>Status</th>
                    <th class="text-end">Receipt</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <!-- Date & Time -->
                    <td class="small text-nowrap">
                        <div class="fw-medium text-dark">{{ $payment->paid_at?->format('d M Y') ?? '—' }}</div>
                        <div class="text-muted" style="font-size: 0.74rem;">{{ $payment->paid_at?->format('h:i A') }}</div>
                    </td>

                    <!-- Receipt No -->
                    <td class="font-monospace small fw-bold text-primary">
                        {{ $payment->receipt_number }}
                    </td>

                    <!-- Renter Name & Room -->
                    <td>
                        <div class="fw-bold text-dark">{{ $payment->student_name }}</div>
                        <div class="text-muted small" style="font-size: 0.76rem;">
                            @if($payment->student?->room)
                                Room {{ $payment->student->room->room_number }}
                                @if($payment->student->bed) &bull; Bed {{ $payment->student->bed->bed_number }} @endif
                            @else
                                {{ $payment->property?->name ?? 'Lodge Renter' }}
                            @endif
                        </div>
                    </td>

                    <!-- Amount Paid -->
                    <td class="text-nowrap">
                        <span class="fs-6 fw-bold text-success">₹{{ number_format($payment->amount, 2) }}</span>
                    </td>

                    <!-- Method -->
                    <td>
                        @php
                            $methodType = $payment->payment_method_type ?? $payment->method;
                        @endphp
                        @if($methodType === 'qr_code')
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center gap-1">
                                <i class="bi bi-qr-code-scan"></i> QR Code Scan
                            </span>
                        @elseif($methodType === 'upi_id' || $methodType === 'upi')
                            <span class="badge bg-indigo-subtle text-indigo border border-indigo-subtle d-inline-flex align-items-center gap-1" style="background-color: #e0e7ff; color: #4338ca;">
                                <i class="bi bi-upc-scan"></i> UPI ID / App
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
                            <span class="badge bg-light text-dark border">{{ strtoupper($payment->method) }}</span>
                        @endif
                    </td>

                    <!-- Paid From (Payer Info) -->
                    <td>
                        @if(!empty($payment->payer_upi_id))
                            <div class="fw-semibold text-dark small font-monospace">
                                <i class="bi bi-at text-primary"></i> {{ $payment->payer_upi_id }}
                            </div>
                        @endif
                        @if(!empty($payment->payer_phone))
                            <div class="text-muted small">
                                <i class="bi bi-telephone text-secondary"></i> {{ $payment->payer_phone }}
                            </div>
                        @endif
                        @if(empty($payment->payer_upi_id) && empty($payment->payer_phone))
                            @if($payment->student?->mobile)
                                <div class="text-muted small">
                                    <i class="bi bi-telephone"></i> {{ $payment->student->mobile }}
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        @endif
                        @if(!empty($payment->transaction_reference))
                            <div class="text-muted" style="font-size: 0.72rem;">
                                Ref/UTR: <span class="font-monospace">{{ $payment->transaction_reference }}</span>
                            </div>
                        @endif
                    </td>

                    <!-- Received In (Owner Account) -->
                    <td>
                        @if(!empty($payment->received_to_account))
                            <span class="badge bg-light text-dark border small fw-normal">
                                <i class="bi bi-arrow-down-left text-success me-1"></i>{{ $payment->received_to_account }}
                            </span>
                        @else
                            <span class="text-muted small">Niwasthal Account</span>
                        @endif
                    </td>

                    <!-- Status -->
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> Completed
                        </span>
                    </td>

                    <!-- Receipt Button -->
                    <td class="text-end">
                        <a href="{{ route('owner.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="View & Print Official Receipt">
                            <i class="bi bi-receipt me-1"></i> Receipt
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <div class="display-6 text-muted opacity-25 mb-2"><i class="bi bi-arrow-left-right"></i></div>
                        <h6 class="fw-bold mb-1">No Transactions Found</h6>
                        <p class="small text-muted mb-0">Payments recorded by renters will appear here in real-time.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $payments->links() }}
</div>
@endsection
