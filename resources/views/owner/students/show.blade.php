@extends('layouts.owner')

@section('title', $student->name . ' — Niwasthal')
@section('page-title', ($occupantTerm ?? 'Student') . ' Profile')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <a href="{{ route('owner.students.index') }}" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left me-1"></i> All {{ $occupantTermPlural ?? 'Students' }}</a>
        <h4 class="fw-bold mb-0 mt-1">{{ $student->name }}</h4>
        <span class="badge bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($student->status) }}</span>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('owner.students.generate-rent', $student) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-receipt me-1"></i> Generate Current Month Rent</button>
        </form>
        <a href="{{ route('owner.students.edit', $student) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
        @if($student->status === 'active')
        <form action="{{ route('owner.students.destroy', $student) }}" method="POST" onsubmit="return confirm('Remove/vacate this {{ strtolower($occupantTerm ?? 'student') }}?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">Remove / Vacate</button>
        </form>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="lm-card p-4 mb-4">
            <h6 class="fw-bold mb-3">Details</h6>
            <div class="small mb-2"><span class="text-muted">Mobile:</span> <strong>{{ $student->mobile }}</strong></div>
            <div class="small mb-2"><span class="text-muted">Email:</span> {{ $student->email ?? '-' }}</div>
            <div class="small mb-2"><span class="text-muted">Property:</span> {{ $student->property?->name }}</div>
            <div class="small mb-2">
                <span class="text-muted">Room / Bed:</span>
                @if($student->bed)
                    Room {{ $student->room_number }} / Bed {{ $student->bed->label }}
                @elseif($student->room)
                    Room {{ $student->room->room_number }} ({{ ucfirst($student->room->type) }})
                @else
                    Unassigned
                @endif
            </div>
            <div class="small mb-2"><span class="text-muted">Monthly Rent:</span> <strong>₹{{ number_format($student->monthly_rent, 2) }}</strong></div>
            <div class="small mb-2"><span class="text-muted">Security Deposit:</span> <strong>₹{{ number_format($student->securityDeposit?->amount ?? $student->security_deposit, 2) }}</strong>
                @if($student->securityDeposit)
                    <span class="badge bg-info-subtle text-info ms-1">Balance: ₹{{ number_format($student->securityDeposit->balance, 2) }} ({{ ucfirst($student->securityDeposit->status) }})</span>
                @endif
            </div>
            <div class="small mb-2"><span class="text-muted">Joining Date:</span> {{ $student->joining_date?->format('d M Y') }}</div>
            @if($student->leaving_date)
                <div class="small mb-2 text-danger"><span class="text-muted">Leaving Date:</span> {{ $student->leaving_date->format('d M Y') }}</div>
            @endif
            <div class="small mb-0"><span class="text-muted">Emergency:</span> {{ $student->emergency_contact_mobile ?? '-' }}</div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="lm-card overflow-hidden mb-4">
            <div class="p-3 border-bottom"><h6 class="fw-bold mb-0">Rent Records</h6></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Month</th><th>Total</th><th>Paid</th><th>Outstanding</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($student->rentRecords as $rec)
                        <tr>
                            <td>{{ date('M', mktime(0,0,0,$rec->month,1)) }} {{ $rec->year }}</td>
                            <td>₹{{ number_format($rec->total_amount, 2) }}</td>
                            <td class="text-success">₹{{ number_format($rec->paid_amount, 2) }}</td>
                            <td class="text-danger fw-semibold">₹{{ number_format($rec->outstanding, 2) }}</td>
                            <td><span class="badge bg-{{ $rec->status === 'paid' ? 'success' : ($rec->status === 'partial' ? 'warning' : 'danger') }}">{{ ucfirst($rec->status) }}</span></td>
                            <td><a href="{{ route('owner.rent.show', $rec) }}" class="btn btn-sm btn-light border">View</a></td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No rent records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
