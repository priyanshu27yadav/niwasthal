@extends('layouts.owner')

@section('title', 'Edit Property — Niwasthal')
@section('page-title', 'Edit Property')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Edit {{ $property->name }}</h5>
            <form action="{{ route('owner.properties.update', $property) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Property Name *</label>
                        <input type="text" name="name" value="{{ old('name', $property->name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Property Type *</label>
                        <select name="type" class="form-select">
                            <option value="pg" {{ old('type', $property->type) == 'pg' ? 'selected' : '' }}>PG</option>
                            <option value="hostel" {{ old('type', $property->type) == 'hostel' ? 'selected' : '' }}>Hostel</option>
                            <option value="lodge" {{ old('type', $property->type) == 'lodge' ? 'selected' : '' }}>Lodge</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Street Address *</label>
                        <textarea name="address" rows="2" class="form-control" required>{{ old('address', $property->address) }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">City *</label>
                        <input type="text" name="city" value="{{ old('city', $property->city) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">State</label>
                        <input type="text" name="state" value="{{ old('state', $property->state) }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Pincode</label>
                        <input type="text" name="pincode" value="{{ old('pincode', $property->pincode) }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contact Number</label>
                        <input type="text" name="contact_number" value="{{ old('contact_number', $property->contact_number) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contact Email</label>
                        <input type="email" name="email" value="{{ old('email', $property->email) }}" class="form-control">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Facilities</label>
                        <input type="text" name="facilities" value="{{ old('facilities', $property->facilities) }}" class="form-control">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" {{ $property->is_active ? 'checked' : '' }}>
                            <label class="form-check-label small fw-semibold" for="isActive">Property is Active</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.properties.show', $property) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Property</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
