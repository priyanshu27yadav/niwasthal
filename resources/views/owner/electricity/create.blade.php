@extends('layouts.owner')

@section('title', 'Create Electricity Bill — Niwasthal')
@section('page-title', 'Create Electricity Bill')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="lm-card p-4" x-data="{ units: 0, rate: 8, fixed: 0, get total() { return (parseFloat(this.units || 0) * parseFloat(this.rate || 0)) + parseFloat(this.fixed || 0); } }">
            <h5 class="fw-bold mb-4">New Electricity Bill</h5>
            <form action="{{ route('owner.electricity.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Property *</label>
                        <select name="property_id" class="form-select" required>
                            @foreach($properties as $prop)
                                <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Month *</label>
                        <select name="month" class="form-select" required>
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}" {{ $num == now()->month ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Year *</label>
                        <select name="year" class="form-select" required>
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $y == now()->year ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Total Units (kWh) *</label>
                        <input type="number" name="total_units" x-model="units" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Rate / Unit (₹) *</label>
                        <input type="number" name="rate_per_unit" x-model="rate" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Fixed Charges (₹)</label>
                        <input type="number" name="fixed_charges" x-model="fixed" step="0.01" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Split Type *</label>
                        <select name="split_type" class="form-select">
                            <option value="equal">Equal Split Across Active Students</option>
                            <option value="manual">Manual Allocation</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Calculated Total (₹)</label>
                        <input type="text" :value="'₹' + total.toFixed(2)" class="form-control fw-bold bg-light" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.electricity.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
