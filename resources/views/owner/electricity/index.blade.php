@extends('layouts.owner')

@section('title', 'Electricity Bills — Niwasthal')
@section('page-title', 'Electricity Bills')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Electricity Bills</h4>
        <p class="text-muted small mb-0">Manage monthly meter readings and student electricity splits.</p>
    </div>
    <a href="{{ route('owner.electricity.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Create Bill
    </a>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Property</th>
                    <th>Period</th>
                    <th>Units</th>
                    <th>Rate/Unit</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bills as $bill)
                <tr>
                    <td class="fw-semibold">{{ $bill->property?->name ?? '-' }}</td>
                    <td>{{ date('F', mktime(0,0,0,$bill->month,1)) }} {{ $bill->year }}</td>
                    <td>{{ number_format($bill->total_units, 1) }} kWh</td>
                    <td>₹{{ number_format($bill->rate_per_unit, 2) }}</td>
                    <td class="fw-bold">₹{{ number_format($bill->total_amount, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $bill->status === 'paid' ? 'success' : ($bill->status === 'generated' ? 'info' : 'secondary') }}">
                            {{ ucfirst($bill->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('owner.electricity.show', $bill) }}" class="btn btn-sm btn-light border">View & Split</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No electricity bills created yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $bills->links() }}</div>
@endsection
