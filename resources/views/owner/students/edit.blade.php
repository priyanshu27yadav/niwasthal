@extends('layouts.owner')

@section('title', 'Edit ' . ($occupantTerm ?? 'Student') . ' — ' . $student->name)
@section('page-title', 'Edit ' . ($occupantTerm ?? 'Student'))

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Edit {{ $student->name }}</h5>
            <form action="{{ route('owner.students.update', $student) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name', $student->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mobile *</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $student->mobile) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" value="{{ old('email', $student->email) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Monthly Rent (₹) *</label>
                        <input type="number" name="monthly_rent" value="{{ old('monthly_rent', $student->monthly_rent) }}" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Bed Allocation</label>
                        <select name="bed_id" class="form-select">
                            @foreach($beds as $bed)
                                <option value="{{ $bed->id }}" {{ old('bed_id', $student->bed_id) == $bed->id ? 'selected' : '' }}>
                                    Room {{ $bed->room->room_number }} - Bed {{ $bed->label }} {{ $bed->id == $student->bed_id ? '(Current)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Emergency Contact Mobile</label>
                        <input type="text" name="emergency_contact_mobile" value="{{ old('emergency_contact_mobile', $student->emergency_contact_mobile) }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Address</label>
                        <textarea name="address" rows="2" class="form-control">{{ old('address', $student->address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $student->notes) }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.students.show', $student) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
