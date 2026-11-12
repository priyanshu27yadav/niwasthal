@extends('layouts.owner')

@section('title', 'Expense Report — Niwasthal')
@section('page-title', 'Expense Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('owner.reports.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> All Reports</a>
        <h4 class="fw-bold mb-0 mt-1">Expense Report ({{ $summary['year'] }})</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'excel']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel</a>
    </div>
</div>

<div class="lm-stat-card mb-4">
    <div class="small text-muted">Total Expenses</div>
    <div class="fs-3 fw-bold text-danger">₹{{ number_format($summary['totalAmount'], 2) }}</div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Date</th><th>Property</th><th>Category</th><th>Description</th><th>Vendor</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse($expenses as $e)
                <tr>
                    <td class="small">{{ $e->expense_date?->format('d M Y') }}</td>
                    <td>{{ $e->property?->name ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark border text-capitalize">{{ $e->category }}</span></td>
                    <td>{{ $e->description }}</td>
                    <td>{{ $e->vendor ?? '-' }}</td>
                    <td class="fw-bold text-danger">₹{{ number_format($e->amount, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No expenses for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
