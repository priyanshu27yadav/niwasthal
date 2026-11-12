@extends('layouts.owner')

@section('title', 'Create Rent Record — Niwasthal')
@section('page-title', 'Create Rent Record')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Manual Rent Bill</h5>
            <form action="{{ route('owner.rent.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Student *</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">Select Student</option>
                        @foreach($students as $stu)
                            <option value="{{ $stu->id }}">{{ $stu->name }} (₹{{ number_format($stu->monthly_rent) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Month *</label>
                        <select name="month" class="form-select" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $m == now()->month ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Year *</label>
                        <input type="number" name="year" value="{{ now()->year }}" class="form-control" required>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Rent Amount (₹) *</label>
                        <input type="number" name="rent_amount" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Late Fee (₹)</label>
                        <input type="number" name="late_fee" value="0" step="0.01" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Due Date</label>
                    <input type="date" name="due_date" value="{{ now()->startOfMonth()->addDays(4)->format('Y-m-d') }}" class="form-control">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('owner.rent.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Rent Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
