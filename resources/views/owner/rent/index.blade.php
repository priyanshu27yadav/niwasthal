@extends('layouts.owner')

@section('title', 'Rent & Dues — Niwasthal')
@section('page-title', 'Rent & Dues')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Rent Records</h4>
        <p class="text-muted small mb-0">Track monthly rent bills, partial payments, and outstanding dues.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('owner.rent.create') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Create Manual Rent
        </a>
        <form action="{{ route('owner.rent.generate-monthly') }}" method="POST" class="d-inline-flex align-items-center gap-1">
            @csrf
            <select name="month" class="form-select form-select-sm" style="width: auto;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ (int) request('month', now()->month) === $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
            <select name="year" class="form-select form-select-sm" style="width: auto;">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ (int) request('year', now()->year) === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Generate rent records for all active {{ strtolower($occupantTermPlural ?? 'students') }} for the selected month?')">
                <i class="bi bi-lightning-fill me-1"></i> Generate Monthly Rent
            </button>
        </form>
    </div>
</div>

<!-- Filters -->
<div class="lm-card p-3 mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Property</label>
            <select name="property_id" class="form-select form-select-sm">
                <option value="">All Properties</option>
                @foreach($properties as $prop)
                    <option value="{{ $prop->id }}" {{ request('property_id') == $prop->id ? 'selected' : '' }}>{{ $prop->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Month</label>
            <select name="month" class="form-select form-select-sm">
                <option value="">All Months</option>
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Year</label>
            <select name="year" class="form-select form-select-sm">
                <option value="">All Years</option>
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm flex-grow-1">Filter</button>
            <a href="{{ route('owner.rent.index') }}" class="btn btn-light btn-sm border">Reset</a>
        </div>
    </form>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Room</th>
                    <th>Period</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rentRecords as $record)
                <tr>
                    <td class="fw-semibold">{{ $record->student_name }}</td>
                    <td>{{ $record->room_number ?? '-' }}</td>
                    <td>{{ date('M', mktime(0,0,0,$record->month,1)) }} {{ $record->year }}</td>
                    <td>₹{{ number_format($record->total_amount, 2) }}</td>
                    <td class="text-success">₹{{ number_format($record->paid_amount, 2) }}</td>
                    <td class="text-danger fw-semibold">₹{{ number_format($record->outstanding, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $record->status === 'paid' ? 'success' : ($record->status === 'partial' ? 'warning' : 'danger') }}">
                            {{ ucfirst($record->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('owner.rent.show', $record) }}" class="btn btn-sm btn-primary">
                            {{ $record->outstanding > 0 ? 'Record Pay' : 'View' }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No rent records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $rentRecords->links() }}</div>
@endsection
