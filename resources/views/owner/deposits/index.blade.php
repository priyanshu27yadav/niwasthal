@extends('layouts.owner')

@section('title', 'Security Deposits — Niwasthal')
@section('page-title', 'Security Deposits')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Security Deposits</h4>
        <p class="text-muted small mb-0">Track collected deposits, deductions, and refunds.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addDepositModal">
        <i class="bi bi-plus-lg me-1"></i> Collect Deposit
    </button>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>Student</th><th>Property</th><th>Deposit Amount</th><th>Current Balance</th><th>Collected On</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($deposits as $deposit)
                <tr>
                    <td class="fw-semibold">{{ $deposit->student_name }}</td>
                    <td>{{ $deposit->property?->name ?? '-' }}</td>
                    <td>₹{{ number_format($deposit->amount, 2) }}</td>
                    <td class="fw-bold text-primary">₹{{ number_format($deposit->balance, 2) }}</td>
                    <td class="small text-muted">{{ $deposit->collected_on?->format('d M Y') }}</td>
                    <td><span class="badge bg-{{ $deposit->status === 'held' ? 'info' : ($deposit->status === 'refunded' ? 'secondary' : 'warning') }}">{{ str_replace('_', ' ', ucfirst($deposit->status)) }}</span></td>
                    <td><a href="{{ route('owner.deposits.show', $deposit) }}" class="btn btn-sm btn-light border">Manage</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No security deposits recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $deposits->links() }}</div>

<!-- Add Deposit Modal -->
<div class="modal fade" id="addDepositModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('owner.deposits.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title fw-bold">Collect Security Deposit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Student *</label>
                    <select name="student_id" class="form-select" required>
                        @foreach($students ?? [] as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->name }} ({{ $stu->property?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Amount (₹) *</label>
                    <input type="number" name="amount" step="0.01" min="1" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Collected On *</label>
                    <input type="date" name="collected_on" value="{{ date('Y-m-d') }}" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">Notes</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Deposit</button>
            </div>
        </form>
    </div>
</div>
@endsection
