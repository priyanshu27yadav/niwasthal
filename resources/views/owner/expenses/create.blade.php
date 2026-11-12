@extends('layouts.owner')

@section('title', 'Log Expense — Niwasthal')
@section('page-title', 'Log Expense')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Add New Expense</h5>
            <form action="{{ route('owner.expenses.store') }}" method="POST" enctype="multipart/form-data">
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
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category *</label>
                        <select name="category" class="form-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description *</label>
                        <input type="text" name="description" value="{{ old('description') }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Amount (₹) *</label>
                        <input type="number" name="amount" value="{{ old('amount') }}" step="0.01" min="1" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Date *</label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Vendor</label>
                        <input type="text" name="vendor" value="{{ old('vendor') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Receipt (Optional)</label>
                        <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.expenses.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
