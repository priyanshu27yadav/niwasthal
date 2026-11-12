@extends('layouts.owner')

@section('title', 'Expenses — Niwasthal')
@section('page-title', 'Expenses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Property Expenses</h4>
        <p class="text-muted small mb-0">Track electricity, water, maintenance, staff salaries, and repairs.</p>
    </div>
    <a href="{{ route('owner.expenses.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Log Expense
    </a>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>Date</th><th>Property</th><th>Category</th><th>Description</th><th>Vendor</th><th>Amount</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                <tr>
                    <td class="small text-muted">{{ $expense->expense_date?->format('d M Y') }}</td>
                    <td>{{ $expense->property?->name ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark border text-capitalize">{{ $expense->category }}</span></td>
                    <td class="fw-medium">{{ $expense->description }}</td>
                    <td class="small">{{ $expense->vendor ?? '-' }}</td>
                    <td class="fw-bold text-danger">₹{{ number_format($expense->amount, 2) }}</td>
                    <td>
                        <a href="{{ route('owner.expenses.edit', $expense) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('owner.expenses.destroy', $expense) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this expense?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No expenses logged yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $expenses->links() }}</div>
@endsection
