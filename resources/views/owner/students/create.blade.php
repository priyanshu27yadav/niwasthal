@extends('layouts.owner')

@section('title', 'Add ' . ($occupantTerm ?? 'Student') . ' — Niwasthal')
@section('page-title', 'Add ' . ($occupantTerm ?? 'Student'))

@section('content')
@php
    $propData = $properties->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'rooms' => $p->rooms->map(function($r) {
                return [
                    'id' => $r->id,
                    'room_number' => $r->room_number,
                    'type' => $r->type,
                    'is_unit_type' => $r->isUnitType(),
                    'rent_amount' => $r->rent_amount,
                    'rent_basis' => $r->rent_basis_label,
                    'beds' => $r->beds->map(fn($b) => ['id' => $b->id, 'label' => $b->label])->values(),
                ];
            })->values(),
        ];
    })->values();
@endphp

<div class="row justify-content-center" x-data="studentOnboarding(@js($propData))">
    <div class="col-lg-9">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Onboard New {{ $occupantTerm ?? 'Student' }}</h5>
            <form action="{{ route('owner.students.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Full Name *</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Mobile Number (Used for OTP Login) *</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control" maxlength="15" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Father's Name</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control">
                    </div>

                    <div class="col-12 mt-4"><h6 class="fw-bold border-bottom pb-2">Room & Bed Allocation</h6></div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Property *</label>
                        <select name="property_id" x-model="propertyId" @change="onPropertyChange()" class="form-select" required>
                            <option value="">Select Property</option>
                            <template x-for="p in properties" :key="p.id">
                                <option :value="p.id" x-text="p.name"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Room *</label>
                        <select name="room_id" x-model="roomId" @change="onRoomChange()" class="form-select" :disabled="!propertyId" required>
                            <option value="">Select Room</option>
                            <template x-for="r in availableRooms" :key="r.id">
                                <option :value="r.id" x-text="'Room ' + r.room_number + (r.is_unit_type ? ' (' + r.type.toUpperCase() + ' - Per Room)' : ' (' + r.beds.length + ' free)')"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-md-4" x-show="!selectedRoom || !selectedRoom.is_unit_type">
                        <label class="form-label small fw-semibold">Bed *</label>
                        <select name="bed_id" x-model="bedId" class="form-select" :disabled="!roomId || (selectedRoom && selectedRoom.is_unit_type)" :required="selectedRoom && !selectedRoom.is_unit_type">
                            <option value="">Select Bed</option>
                            <template x-for="b in availableBeds" :key="b.id">
                                <option :value="b.id" x-text="'Bed ' + b.label"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-md-4" x-show="selectedRoom && selectedRoom.is_unit_type" style="display: none;">
                        <label class="form-label small fw-semibold">Allocation Type</label>
                        <div class="form-control bg-info-subtle text-info fw-semibold border-info-subtle">
                            Whole Unit (No Bed Required)
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Joining Date *</label>
                        <input type="date" name="joining_date" value="{{ old('joining_date', date('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Monthly Rent (₹) *</label>
                        <input type="number" name="monthly_rent" x-model="monthlyRent" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Security Deposit (₹)</label>
                        <input type="number" name="security_deposit" value="{{ old('security_deposit', 0) }}" step="0.01" min="0" class="form-control">
                    </div>

                    <div class="col-12 mt-4"><h6 class="fw-bold border-bottom pb-2">Address, ID & Emergency Contact</h6></div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Permanent Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">City</label>
                        <input type="text" name="city" value="{{ old('city') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">ID Proof Type</label>
                        <select name="id_proof_type" class="form-select">
                            <option value="">Select</option>
                            <option value="aadhar">Aadhar Card</option>
                            <option value="pan">PAN Card</option>
                            <option value="dl">Driving License</option>
                            <option value="passport">Passport</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">ID Proof Number</label>
                        <input type="text" name="id_proof_number" value="{{ old('id_proof_number') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Emergency Mobile</label>
                        <input type="text" name="emergency_contact_mobile" value="{{ old('emergency_contact_mobile') }}" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('owner.students.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Register {{ $occupantTerm ?? 'Student' }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function studentOnboarding(properties) {
    return {
        properties,
        propertyId: '{{ old('property_id', request('property_id', '')) }}',
        roomId: '{{ old('room_id', request('room_id', '')) }}',
        bedId: '{{ old('bed_id', request('bed_id', '')) }}',
        monthlyRent: '{{ old('monthly_rent', '') }}',
        get availableRooms() {
            const p = this.properties.find(x => String(x.id) === String(this.propertyId));
            return p ? p.rooms.filter(r => r.is_unit_type || r.beds.length > 0) : [];
        },
        get selectedRoom() {
            return this.availableRooms.find(x => String(x.id) === String(this.roomId)) || null;
        },
        get availableBeds() {
            return this.selectedRoom ? this.selectedRoom.beds : [];
        },
        onPropertyChange() {
            this.roomId = '';
            this.bedId = '';
        },
        onRoomChange() {
            this.bedId = '';
            if (this.selectedRoom && this.selectedRoom.rent_amount) {
                this.monthlyRent = this.selectedRoom.rent_amount;
            }
        }
    }
}
</script>
@endpush
