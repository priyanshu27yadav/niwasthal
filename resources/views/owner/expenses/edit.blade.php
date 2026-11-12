@extends('layouts.owner')

@section('title', 'Edit Expense — Niwasthal')
@section('page-title', 'Edit Expense')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Edit Expense</h5>
            <form action="{{ route('owner.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Property *</label>
                        <select name="property_id" class="form-select" required>
                            @foreach($properties as $prop)
                                <option value="{{ $prop->id }}" {{ $expense->property_id == $prop->id ? 'selected' : '' }}>{{ $prop->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category *</label>
                        <select name="category" class="form-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $expense->category == $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description *</label>
                        <input type="text" name="description" value="{{ old('description', $expense->description) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Amount (₹) *</label>
                        <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Date *</label>
                        <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Vendor</label>
                        <input type="text" name="vendor" value="{{ old('vendor', $expense->vendor) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $expense->notes) }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.expenses.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
