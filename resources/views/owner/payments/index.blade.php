@extends('layouts.owner')

@section('title', 'Payments — Niwasthal')
@section('page-title', 'Payments History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Payments</h4>
        <p class="text-muted small mb-0">All rent and fee payments collected across your properties.</p>
    </div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Receipt No</th>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Paid At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td class="font-monospace small fw-semibold">{{ $payment->receipt_number }}</td>
                    <td class="fw-medium">{{ $payment->student_name }}</td>
                    <td class="text-success fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                    <td><span class="badge bg-light text-dark border text-uppercase">{{ $payment->method }}</span></td>
                    <td class="small text-muted">{{ $payment->paid_at?->format('d M Y, h:i A') }}</td>
                    <td><span class="badge bg-success-subtle text-success">{{ ucfirst($payment->status) }}</span></td>
                    <td>
                        <a href="{{ route('owner.payments.receipt', $payment) }}" class="btn btn-sm btn-light border">
                            <i class="bi bi-printer me-1"></i> Receipt
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
