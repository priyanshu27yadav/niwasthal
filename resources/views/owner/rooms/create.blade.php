@extends('layouts.owner')

@section('title', 'Add Room — ' . $property->name)
@section('page-title', 'Add Room')

@section('content')
<div class="row justify-content-center"
     x-data="{
        type: @js(old('type', 'double')),
        capacity: {{ (int) old('capacity', 2) }},
        rentType: @js(old('rent_type', 'per_bed')),
        isUnitType() { return this.type === 'family' || this.type === 'shop'; },
        onTypeChange() {
            const map = { single: 1, double: 2, triple: 3, four_sharing: 4 };
            if (this.isUnitType()) {
                this.capacity = 0;
                this.rentType = 'per_room';
            } else {
                if (map[this.type]) this.capacity = map[this.type];
                else if (this.capacity < 1) this.capacity = 1;
            }
        }
     }">
    <div class="col-lg-6">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Add Room / Unit to {{ $property->name }}</h5>
            <form action="{{ route('owner.properties.rooms.store', $property) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room / Unit Number *</label>
                        <input type="text" name="room_number" value="{{ old('room_number') }}" class="form-control" placeholder="e.g. R104, S01" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Floor</label>
                        <input type="text" name="floor" value="{{ old('floor') }}" class="form-control" placeholder="e.g. Ground Floor, 1st Floor">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room Type *</label>
                        <select name="type" x-model="type" @change="onTypeChange()" class="form-select" required>
                            <option value="single">Single</option>
                            <option value="double">Double Sharing</option>
                            <option value="triple">Triple Sharing</option>
                            <option value="four_sharing">Four Sharing</option>
                            <option value="custom">Custom</option>
                            <option value="family">Family (No Beds)</option>
                            <option value="shop">Shop (No Beds)</option>
                        </select>
                    </div>
                    <div class="col-md-6" x-show="!isUnitType()">
                        <label class="form-label small fw-semibold">Capacity (Beds) *</label>
                        <input type="number" name="capacity" x-model.number="capacity" :disabled="isUnitType()" min="1" max="26" class="form-control" :required="!isUnitType()">
                    </div>
                    <div class="col-md-6" x-show="isUnitType()" style="display: none;">
                        <label class="form-label small fw-semibold">Unit Type Info</label>
                        <input type="hidden" name="capacity" value="0" :disabled="!isUnitType()">
                        <div class="form-control bg-info-subtle text-info fw-semibold border-info-subtle">
                            No Beds Required (Whole Unit)
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Charge Rent By *</label>
                        <select name="rent_type" x-model="rentType" class="form-select" required>
                            <option value="per_bed" x-show="!isUnitType()" :disabled="isUnitType()">Per Bed</option>
                            <option value="per_room">Per Room</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" x-text="'Monthly Rent (₹ ' + (rentType === 'per_room' ? 'per Room' : 'per Bed') + ') *'"></label>
                        <input type="number" name="rent_amount" value="{{ old('rent_amount', 6500) }}" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.properties.rooms.index', $property) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary" x-text="isUnitType() ? 'Create Unit (Per Room)' : 'Create Room & Auto-Generate Beds'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
