@extends('layouts.owner')

@section('title', 'Edit Room ' . $room->room_number)
@section('page-title', 'Edit Room ' . $room->room_number)

@section('content')
<div class="row justify-content-center"
     x-data="{
        type: @js(old('type', $room->type)),
        capacity: {{ (int) old('capacity', $room->capacity) }},
        rentType: @js(old('rent_type', $room->rent_type ?? ($room->isUnitType() ? 'per_room' : 'per_bed'))),
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
            <h5 class="fw-bold mb-4">Edit Room {{ $room->room_number }}</h5>
            <form action="{{ route('owner.properties.rooms.update', [$property, $room]) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room / Unit Number *</label>
                        <input type="text" name="room_number" value="{{ old('room_number', $room->room_number) }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Floor</label>
                        <input type="text" name="floor" value="{{ old('floor', $room->floor) }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Room Type *</label>
                        <select name="type" x-model="type" @change="onTypeChange()" class="form-select" required>
                            @foreach(['single' => 'Single', 'double' => 'Double Sharing', 'triple' => 'Triple Sharing', 'four_sharing' => 'Four Sharing', 'custom' => 'Custom', 'family' => 'Family (No Beds)', 'shop' => 'Shop (No Beds)'] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
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
                        <input type="number" name="rent_amount" value="{{ old('rent_amount', $room->rent_amount) }}" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description', $room->description) }}</textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.properties.rooms.show', [$property, $room]) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
