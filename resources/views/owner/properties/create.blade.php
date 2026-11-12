@extends('layouts.owner')

@section('title', 'Add Property (2 Easy Steps) — Niwasthal')
@section('page-title', 'Add Property & Rooms')

@section('content')
@php
    $oldRooms = old('rooms', []);
    $oldTotalFloors = (int) old('total_floors', 2);
    $oldFloorCounts = old('floor_counts', $oldTotalFloors === 2 ? [2, 2] : array_fill(0, max(1, $oldTotalFloors), 2));
@endphp

<div class="row justify-content-center"
     x-data="propertyWizard({
        initialStep: {{ count($oldRooms) > 0 ? 2 : 1 }},
        initialTotalFloors: {{ max(1, min(15, $oldTotalFloors)) }},
        initialFloorCounts: @js(array_map('intval', $oldFloorCounts)),
        initialRooms: @js(array_values($oldRooms))
     })">
    <div class="col-xl-10">
        <!-- 2-Step Progress Header -->
        <div class="lm-card p-3 p-md-4 mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <button type="button" @click="step = 1"
                            class="btn rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-2"
                            :class="step === 1 ? 'btn-primary' : 'btn-outline-success'">
                        <span class="badge rounded-pill" :class="step === 1 ? 'bg-white text-primary' : 'bg-success text-white'">
                            <i class="bi bi-check-lg" x-show="step === 2"></i>
                            <span x-show="step === 1">1</span>
                        </span>
                        Step 1: Property &amp; Floors
                    </button>

                    <i class="bi bi-arrow-right text-muted d-none d-sm-inline"></i>

                    <button type="button" @click="goToStep2()"
                            class="btn rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-2"
                            :class="step === 2 ? 'btn-primary' : 'btn-outline-secondary'">
                        <span class="badge rounded-pill" :class="step === 2 ? 'bg-white text-primary' : 'bg-secondary text-white'">2</span>
                        Step 2: Add Rooms
                    </button>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary px-3 py-2">
                        <i class="bi bi-layers me-1"></i>
                        <span x-text="totalFloors"></span> Floor(s)
                    </span>
                    <span class="badge bg-success-subtle text-success px-3 py-2">
                        <i class="bi bi-door-open me-1"></i>
                        <span x-text="step === 2 ? rooms.length : totalFloorRooms()"></span> Room(s)
                    </span>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please fix the following errors:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('owner.properties.store') }}" method="POST" id="propertyWizardForm">
            @csrf

            <!-- STEP 1: PROPERTY DETAILS + FLOOR QUESTIONS -->
            <div class="lm-card p-4" x-show="step === 1">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Step 1: Property Details &amp; Floor Breakdown</h5>
                        <p class="text-muted small mb-0">Enter property info and tell us how many floors and rooms are on each floor.</p>
                    </div>
                    <span class="badge bg-light text-dark border">Step 1 of 2</span>
                </div>

                <div class="row g-3" id="step1Fields">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold">Property Name *</label>
                        <input type="text" name="name" x-model="property.name" value="{{ old('name') }}" class="form-control" placeholder="e.g. Sunrise PG" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Property Type *</label>
                        <select name="type" x-model="property.type" class="form-select">
                            <option value="pg">PG (Paying Guest)</option>
                            <option value="hostel">Hostel</option>
                            <option value="lodge">Lodge</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Street Address *</label>
                        <textarea name="address" x-model="property.address" rows="2" class="form-control" placeholder="House/Building No, Street, Area" required>{{ old('address') }}</textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">City *</label>
                        <input type="text" name="city" x-model="property.city" value="{{ old('city') }}" class="form-control" placeholder="e.g. Bangalore" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">State</label>
                        <input type="text" name="state" value="{{ old('state') }}" class="form-control" placeholder="e.g. Karnataka">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Pincode</label>
                        <input type="text" name="pincode" value="{{ old('pincode') }}" class="form-control" maxlength="10">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contact Number</label>
                        <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-control" placeholder="10-digit mobile">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Contact Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Facilities (comma-separated)</label>
                        <input type="text" name="facilities" value="{{ old('facilities') }}" class="form-control" placeholder="WiFi, Laundry, Power Backup, CCTV, RO Water">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Google Maps URL (Optional)</label>
                        <input type="url" name="google_maps_url" value="{{ old('google_maps_url') }}" class="form-control">
                    </div>

                    <!-- Dynamic Floor & Room Count Section -->
                    <div class="col-12 mt-4">
                        <div class="p-3 p-md-4 rounded-3 border bg-light-subtle">
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        <i class="bi bi-layers-fill text-primary me-1"></i> Total Floors in Property *
                                    </label>
                                    <input type="number" name="total_floors" x-model.number="totalFloors"
                                           @input="syncFloors()" min="1" max="15" class="form-control form-control-lg fw-semibold" required>
                                    <div class="form-text">1 = Ground Floor only, 2 = Ground + 1st Floor, etc.</div>
                                </div>
                                <div class="col-md-7">
                                    <div class="alert alert-primary border-0 mb-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="small fw-semibold">Total Rooms Planned</div>
                                            <div class="text-muted small">Answer below how many rooms are on each floor</div>
                                        </div>
                                        <span class="fs-4 fw-bold text-primary" x-text="totalFloorRooms()"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <template x-for="(count, floorIdx) in floorCounts" :key="floorIdx">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="p-3 bg-white rounded-3 border h-100">
                                            <label class="form-label small fw-bold text-primary mb-1"
                                                   x-text="floorLabel(floorIdx) + ' — How many rooms?'"></label>
                                            <input type="number" :name="'floor_counts[' + floorIdx + ']'"
                                                   x-model.number="floorCounts[floorIdx]"
                                                   min="0" max="50" class="form-control" required>
                                            <div class="form-text" x-text="'Auto-creates ' + (parseInt(count) || 0) + ' room(s) on ' + floorLabel(floorIdx)"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <a href="{{ route('owner.properties.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" @click="goToStep2()" class="btn btn-primary btn-lg px-4 fw-semibold">
                        Next: Step 2 — Add Rooms <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 2: CONFIGURE ROOMS PER FLOOR -->
            <div class="lm-card p-4" x-show="step === 2" style="display: none;">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-1">Step 2: Configure Rooms &amp; Monthly Rent</h5>
                        <p class="text-muted small mb-0">
                            Property: <strong x-text="property.name || 'New Property'"></strong>
                            (<span x-text="property.city || '-'"></span>) &bull;
                            <span x-text="totalFloors"></span> Floor(s) &bull;
                            <span x-text="rooms.length"></span> Room(s)
                        </p>
                    </div>
                    <span class="badge bg-light text-dark border">Step 2 of 2</span>
                </div>

                <!-- Quick Bulk Defaults Bar -->
                <div class="p-3 rounded-3 bg-light border mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Default Room Type</label>
                            <select x-model="defaultType" @change="onDefaultTypeChange()" class="form-select form-select-sm">
                                <option value="single">Single (1 Bed)</option>
                                <option value="double">Double (2 Beds)</option>
                                <option value="triple">Triple (3 Beds)</option>
                                <option value="four_sharing">Four Sharing (4 Beds)</option>
                                <option value="custom">Custom</option>
                                <option value="family">Family (No Beds)</option>
                                <option value="shop">Shop (No Beds)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Charge Rent By</label>
                            <select x-model="defaultRentType" class="form-select form-select-sm">
                                <option value="per_bed" x-show="!isUnitType(defaultType)" :disabled="isUnitType(defaultType)">Per Bed</option>
                                <option value="per_room">Per Room</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1" x-text="'Default Rent (₹ ' + (defaultRentType === 'per_room' ? 'per Room' : 'per Bed') + ')'"></label>
                            <input type="number" x-model.number="defaultRent" min="0" step="100" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" @click="applyDefaultsToAll()" class="btn btn-outline-primary btn-sm fw-semibold">
                                <i class="bi bi-magic me-1"></i> Apply Defaults to All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State if 0 rooms -->
                <div x-show="rooms.length === 0" class="text-center py-4 border rounded-3 mb-3">
                    <p class="text-muted mb-2">No rooms generated yet. Go back to set floor room counts or add a room manually.</p>
                    <button type="button" @click="addManualRoom()" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i> Add a Room
                    </button>
                </div>

                <!-- Rooms Table -->
                <div class="table-responsive" x-show="rooms.length > 0">
                    <table class="table align-middle table-hover">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="width: 15%;">Floor</th>
                                <th style="width: 14%;">Room No. *</th>
                                <th style="width: 19%;">Room Type *</th>
                                <th style="width: 14%;">Beds</th>
                                <th style="width: 16%;">Charge By *</th>
                                <th style="width: 16%;">Monthly Rent (₹) *</th>
                                <th style="width: 6%;" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(room, idx) in rooms" :key="idx">
                                <tr>
                                    <td>
                                        <input type="text" :name="'rooms[' + idx + '][floor]'" x-model="room.floor"
                                               class="form-control form-control-sm fw-semibold bg-light" required>
                                    </td>
                                    <td>
                                        <input type="text" :name="'rooms[' + idx + '][room_number]'" x-model="room.room_number"
                                               class="form-control form-control-sm" placeholder="e.g. G01" required>
                                    </td>
                                    <td>
                                        <select :name="'rooms[' + idx + '][type]'" x-model="room.type"
                                                @change="onRoomTypeChange(room)" class="form-select form-select-sm" required>
                                            <option value="single">Single</option>
                                            <option value="double">Double</option>
                                            <option value="triple">Triple</option>
                                            <option value="four_sharing">Four Sharing</option>
                                            <option value="custom">Custom</option>
                                            <option value="family">Family</option>
                                            <option value="shop">Shop</option>
                                        </select>
                                    </td>
                                    <td>
                                        <template x-if="!isUnitType(room.type)">
                                            <input type="number" :name="'rooms[' + idx + '][capacity]'" x-model.number="room.capacity"
                                                   min="1" max="26" class="form-control form-control-sm" required>
                                        </template>
                                        <template x-if="isUnitType(room.type)">
                                            <div>
                                                <input type="hidden" :name="'rooms[' + idx + '][capacity]'" value="0">
                                                <span class="badge bg-info-subtle text-info border w-100 py-2">No Beds</span>
                                            </div>
                                        </template>
                                    </td>
                                    <td>
                                        <select :name="'rooms[' + idx + '][rent_type]'" x-model="room.rent_type"
                                                class="form-select form-select-sm" required>
                                            <option value="per_bed" x-show="!isUnitType(room.type)" :disabled="isUnitType(room.type)">Per Bed</option>
                                            <option value="per_room">Per Room</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" :name="'rooms[' + idx + '][rent_amount]'" x-model.number="room.rent_amount"
                                               min="0" step="50" class="form-control form-control-sm" required>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" @click="removeRoom(idx)" class="btn btn-sm btn-outline-danger" title="Remove room">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-3 border-top">
                    <div class="d-flex gap-2">
                        <button type="button" @click="step = 1" class="btn btn-light border">
                            <i class="bi bi-arrow-left me-1"></i> Back to Step 1
                        </button>
                        <button type="button" @click="addManualRoom()" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Extra Room
                        </button>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg px-4 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i> Save Property &amp; <span x-text="rooms.length"></span> Room(s)
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
window.propertyWizard = function(config) {
    return {
        step: config.initialStep || 1,
        totalFloors: config.initialTotalFloors || 2,
        floorCounts: config.initialFloorCounts && config.initialFloorCounts.length ? config.initialFloorCounts : [2, 2],
        rooms: config.initialRooms || [],
        defaultType: 'double',
        defaultRentType: 'per_bed',
        defaultRent: 7000,
        property: {
            name: @js(old('name', '')),
            type: @js(old('type', auth()->user()->business_type ?? 'pg')),
            address: @js(old('address', '')),
            city: @js(old('city', '')),
        },

        isUnitType(type) {
            return type === 'family' || type === 'shop';
        },

        onDefaultTypeChange() {
            if (this.isUnitType(this.defaultType)) {
                this.defaultRentType = 'per_room';
            }
        },

        floorLabel(index) {
            if (index === 0) return 'Ground Floor';
            if (index === 1) return '1st Floor';
            if (index === 2) return '2nd Floor';
            if (index === 3) return '3rd Floor';
            return index + 'th Floor';
        },

        syncFloors() {
            let n = parseInt(this.totalFloors) || 1;
            if (n < 1) n = 1;
            if (n > 15) n = 15;
            while (this.floorCounts.length < n) {
                this.floorCounts.push(2);
            }
            if (this.floorCounts.length > n) {
                this.floorCounts.splice(n);
            }
        },

        totalFloorRooms() {
            return this.floorCounts.reduce((sum, c) => sum + (parseInt(c) || 0), 0);
        },

        capacityForType(type) {
            const map = { single: 1, double: 2, triple: 3, four_sharing: 4, family: 0, shop: 0 };
            return map[type] ?? 2;
        },

        onRoomTypeChange(room) {
            if (this.isUnitType(room.type)) {
                room.capacity = 0;
                room.rent_type = 'per_room';
            } else {
                if (room.type !== 'custom') {
                    room.capacity = this.capacityForType(room.type);
                } else if (!room.capacity || room.capacity < 1) {
                    room.capacity = 2;
                }
                if (!room.rent_type) {
                    room.rent_type = 'per_bed';
                }
            }
        },

        generateRoomsFromFloors() {
            const existingByKey = {};
            this.rooms.forEach(r => {
                existingByKey[r.floor + '::' + r.room_number] = r;
            });

            const generated = [];
            let seq = 1;
            this.floorCounts.forEach((rawCount, floorIdx) => {
                const count = Math.max(0, Math.min(50, parseInt(rawCount) || 0));
                const floor = this.floorLabel(floorIdx);
                for (let i = 1; i <= count; i++) {
                    const roomNo = String(seq++);
                    const key = floor + '::' + roomNo;
                    if (existingByKey[key]) {
                        generated.push(existingByKey[key]);
                    } else {
                        const unit = this.isUnitType(this.defaultType);
                        generated.push({
                            floor: floor,
                            room_number: roomNo,
                            type: this.defaultType,
                            capacity: this.capacityForType(this.defaultType),
                            rent_type: unit ? 'per_room' : this.defaultRentType,
                            rent_amount: this.defaultRent,
                        });
                    }
                }
            });
            this.rooms = generated;
        },

        goToStep2() {
            const container = document.getElementById('step1Fields');
            const inputs = container ? container.querySelectorAll('input, textarea, select') : [];
            for (const el of inputs) {
                if (!el.checkValidity()) {
                    el.reportValidity();
                    return;
                }
            }
            this.syncFloors();
            this.generateRoomsFromFloors();
            this.step = 2;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        applyDefaultsToAll() {
            const cap = this.capacityForType(this.defaultType);
            const rentType = this.isUnitType(this.defaultType) ? 'per_room' : this.defaultRentType;
            this.rooms.forEach(r => {
                r.type = this.defaultType;
                r.capacity = cap;
                r.rent_type = rentType;
                r.rent_amount = this.defaultRent;
            });
        },

        addManualRoom() {
            const floor = this.floorLabel(0);
            const nextNo = 'R' + (this.rooms.length + 101);
            const unit = this.isUnitType(this.defaultType);
            this.rooms.push({
                floor: floor,
                room_number: nextNo,
                type: this.defaultType,
                capacity: this.capacityForType(this.defaultType),
                rent_type: unit ? 'per_room' : this.defaultRentType,
                rent_amount: this.defaultRent,
            });
        },

        removeRoom(idx) {
            this.rooms.splice(idx, 1);
        }
    };
};
</script>
@endsection
