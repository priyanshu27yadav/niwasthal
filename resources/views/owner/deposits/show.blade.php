@extends('layouts.owner')

@section('title', 'Deposit — ' . $deposit->student_name)
@section('page-title', 'Security Deposit Detail')

@section('content')
<div class="mb-3">
    <a href="{{ route('owner.deposits.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> Back to Deposits</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-1">{{ $deposit->student_name }}</h5>
            <p class="text-muted small mb-3">{{ $deposit->property?->name }}</p>
            <div class="d-flex justify-content-between mb-2"><span>Initial Deposit</span><strong>₹{{ number_format($deposit->amount, 2) }}</strong></div>
            <div class="d-flex justify-content-between mb-3"><span>Current Balance Held</span><strong class="text-primary fs-5">₹{{ number_format($deposit->balance, 2) }}</strong></div>

            @if($deposit->balance > 0)
            <hr>
            <h6 class="fw-bold mb-3">Record Deduction or Refund</h6>
            <form action="{{ route('owner.deposits.transactions.store', $deposit) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Transaction Type</label>
                    <select name="type" class="form-select form-select-sm" required>
                        <option value="deduct">Deduct (Damages / Pending Dues)</option>
                        <option value="refund">Refund to Student</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Amount (₹)</label>
                    <input type="number" name="amount" max="{{ $deposit->balance }}" step="0.01" min="1" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Reason *</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Room painting / Final settlement" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Date</label>
                    <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">Submit Transaction</button>
            </form>
            @endif
        </div>
    </div>

    <div class="col-lg-7">
        <div class="lm-card overflow-hidden">
            <div class="p-3 border-bottom"><h6 class="fw-bold mb-0">Transaction History</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Reason</th></tr></thead>
                    <tbody>
                        @forelse($deposit->transactions as $tx)
                        <tr>
                            <td class="small">{{ $tx->transaction_date?->format('d M Y') }}</td>
                            <td><span class="badge bg-{{ $tx->type === 'collect' ? 'success' : ($tx->type === 'deduct' ? 'danger' : 'info') }}">{{ ucfirst($tx->type) }}</span></td>
                            <td class="fw-bold">₹{{ number_format($tx->amount, 2) }}</td>
                            <td class="small">{{ $tx->reason ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No transactions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
