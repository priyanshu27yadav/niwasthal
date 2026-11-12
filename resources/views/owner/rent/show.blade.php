@extends('layouts.owner')

@section('title', 'Rent Detail — Niwasthal')
@section('page-title', 'Rent Record Detail')

@section('content')
<div class="mb-3">
    <a href="{{ route('owner.rent.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Rent Records</a>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="lm-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">{{ $rent->student_name }}</h5>
                <span class="badge bg-{{ $rent->status === 'paid' ? 'success' : ($rent->status === 'partial' ? 'warning' : 'danger') }} fs-6">
                    {{ ucfirst($rent->status) }}
                </span>
            </div>
            <p class="text-muted small">Room {{ $rent->room_number ?? '-' }} • {{ date('F', mktime(0,0,0,$rent->month,1)) }} {{ $rent->year }}</p>
            <hr>
            <div class="d-flex justify-content-between mb-2"><span>Base Rent</span><strong>₹{{ number_format($rent->rent_amount, 2) }}</strong></div>
            <div class="d-flex justify-content-between mb-2"><span>Late Fee</span><span>₹{{ number_format($rent->late_fee, 2) }}</span></div>
            <div class="d-flex justify-content-between mb-2"><span>Total Billed</span><strong>₹{{ number_format($rent->total_amount, 2) }}</strong></div>
            <div class="d-flex justify-content-between mb-2 text-success"><span>Paid</span><strong>₹{{ number_format($rent->paid_amount, 2) }}</strong></div>
            <div class="d-flex justify-content-between fs-5 text-danger fw-bold pt-2 border-top"><span>Outstanding</span><span>₹{{ number_format($rent->outstanding, 2) }}</span></div>
        </div>
    </div>

    @if($rent->outstanding > 0)
    <div class="col-lg-6">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-3">Record Payment</h5>
            <form action="{{ route('owner.rent.pay', $rent) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Amount (₹) *</label>
                    <input type="number" name="amount" value="{{ $rent->outstanding }}" step="0.01" min="1" class="form-control" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Method *</label>
                        <select name="method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Payment Date</label>
                        <input type="date" name="paid_at" value="{{ date('Y-m-d') }}" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Transaction Reference</label>
                    <input type="text" name="transaction_reference" class="form-control" placeholder="UTR / Ref No">
                </div>
                <button type="submit" class="btn btn-success w-100 fw-semibold">Confirm Payment</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
