@extends('layouts.owner')

@section('title', 'Owner Dashboard — Niwasthal')
@section('page-title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-3 p-md-4 rounded-4 border shadow-sm">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1">
                {{ $isLodgeOwner ? 'Lodge Portal' : 'PG & Hostel Portal' }}
            </span>
            <span class="text-muted small">{{ now()->format('l, d M Y') }}</span>
        </div>
        <h4 class="fw-bold mb-1">Welcome back, {{ auth()->user()->name }}</h4>
        <p class="text-muted small mb-0">
            Live {{ $isLodgeOwner ? 'Lodge' : 'PG & Hostel' }} operations overview for {{ now()->format('F Y') }}.
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('owner.properties.create') }}" class="btn btn-success btn-sm fw-semibold px-3">
            <i class="bi bi-building-add me-1"></i> Add Property (2 Steps)
        </a>
        <a href="{{ route('owner.students.create') }}" class="btn btn-primary btn-sm px-3">
            <i class="bi bi-person-plus-fill me-1"></i> Add {{ $occupantTerm }}
        </a>
        <form action="{{ route('owner.rent.generate-monthly') }}" method="POST" class="d-inline-flex align-items-center gap-1">
            @csrf
            <select name="month" class="form-select form-select-sm" style="width: auto;">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ (int) now()->month === $m ? 'selected' : '' }}>{{ date('M', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
            <select name="year" class="form-select form-select-sm" style="width: auto;">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" {{ (int) now()->year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn btn-outline-primary btn-sm px-3" onclick="return confirm('Generate monthly rent records for all active {{ strtolower($occupantTermPlural) }} for the selected month?')">
                <i class="bi bi-calendar-check me-1"></i> Generate Monthly Rent
            </button>
        </form>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="lm-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium">Active {{ $occupantTermPlural }}</div>
                    <div class="fs-3 fw-bold mt-1">{{ $totalActiveStudents }}</div>
                    <div class="small text-muted mt-1">{{ $totalRooms }} Rooms total</div>
                </div>
                <span class="lm-stat-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="lm-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium">
                        @if($totalUnits > 0 && $totalBeds === 0)
                            Room / Unit Occupancy
                        @elseif($totalUnits > 0)
                            Bed &amp; Unit Occupancy
                        @else
                            Bed Occupancy
                        @endif
                    </div>
                    <div class="fs-3 fw-bold mt-1">{{ $occupancyPercent }}%</div>
                    <div class="small text-muted mt-1">
                        @if($totalUnits > 0 && $totalBeds === 0)
                            {{ $occupiedUnits }} / {{ $totalUnits }} Units ({{ $availableCapacity }} free)
                        @elseif($totalUnits > 0)
                            {{ $occupiedCapacity }} / {{ $totalCapacity }} ({{ $occupiedBeds }}/{{ $totalBeds }} Beds &bull; {{ $occupiedUnits }}/{{ $totalUnits }} Units)
                        @else
                            {{ $occupiedBeds }} / {{ $totalBeds }} Beds ({{ $availableBeds }} free)
                        @endif
                    </div>
                </div>
                <span class="lm-stat-icon bg-info-subtle text-info"><i class="bi bi-door-open-fill"></i></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="lm-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium">Collected ({{ now()->format('M') }})</div>
                    <div class="fs-3 fw-bold text-success mt-1">₹{{ number_format($rentCollected, 2) }}</div>
                    <div class="small text-muted mt-1">Current month revenue</div>
                </div>
                <span class="lm-stat-icon bg-success-subtle text-success"><i class="bi bi-currency-rupee"></i></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="lm-stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted small fw-medium">Outstanding Dues</div>
                    <div class="fs-3 fw-bold text-danger mt-1">₹{{ number_format($outstandingDues, 2) }}</div>
                    <div class="small text-muted mt-1">{{ $pendingComplaints }} Pending complaints</div>
                </div>
                <span class="lm-stat-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-circle-fill"></i></span>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Rooms Live Board -->
<div class="lm-card p-3 p-md-4 mb-4"
     x-data="dashboardRoomsBoard({
        rooms: @js($roomsBoardData),
        initialRoomId: {{ $selectedRoomId }},
        csrfToken: @js(csrf_token()),
        storeUrl: @js(route('owner.students.store')),
        today: @js(now()->toDateString())
     })">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>All Rooms Live Board</h5>
            <p class="text-muted small mb-0">
                Click any room number to view residents/renters, dues, payment history, security deposits, add new people, or view who left earlier.
            </p>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" @click="statusFilter = 'all'" class="btn" :class="statusFilter === 'all' ? 'btn-dark' : 'btn-outline-secondary'">All</button>
                <button type="button" @click="statusFilter = 'available'" class="btn" :class="statusFilter === 'available' ? 'btn-success' : 'btn-outline-secondary'">Available</button>
                <button type="button" @click="statusFilter = 'booked'" class="btn" :class="statusFilter === 'booked' ? 'btn-primary' : 'btn-outline-secondary'">Booked</button>
            </div>
            <input type="text" x-model="search" class="form-control form-control-sm" style="width: 180px;" placeholder="Search room no / name...">
            @if($properties->count() > 1)
                <select x-model="propertyFilter" class="form-select form-select-sm" style="width: 170px;">
                    <option value="">All Properties</option>
                    @foreach($properties as $prop)
                        <option value="{{ $prop->id }}">{{ $prop->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @if($roomsBoardData->isEmpty())
        <div class="text-center py-5 border rounded-3 bg-light">
            <i class="bi bi-building-add fs-1 text-primary d-block mb-2"></i>
            <h6 class="fw-bold">No Properties or Rooms Yet</h6>
            <p class="text-muted small mb-3">Add your property and configure floors &amp; rooms in 2 easy steps.</p>
            <a href="{{ route('owner.properties.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Property (2 Steps)
            </a>
        </div>
    @else
        <div class="row g-4">
            <!-- Left: Clickable Room Number Containers -->
            <div class="col-lg-5">
                <div class="row g-2" style="max-height: 560px; overflow-y: auto; padding: 2px;">
                    <template x-for="room in filteredRooms" :key="room.id">
                        <div class="col-6 col-sm-4 col-lg-6">
                            <button type="button" @click="selectRoom(room)"
                                    class="w-100 text-start lm-room-tile position-relative"
                                    :class="selectedRoom && selectedRoom.id === room.id ? 'is-selected' : ''">
                                <div class="room-bar"
                                     :class="room.active_occupants.length === 0
                                        ? 'bg-success'
                                        : (room.has_vacancy ? 'bg-warning' : 'bg-primary')"></div>
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-bold fs-5 text-dark" x-text="'Room ' + room.room_number"></span>
                                    <span class="badge rounded-pill"
                                          :class="room.active_occupants.length === 0
                                            ? 'bg-success-subtle text-success'
                                            : (room.has_vacancy ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary')"
                                          x-text="room.active_occupants.length === 0
                                            ? 'Available'
                                            : (room.has_vacancy ? 'Partial' : 'Booked')">
                                    </span>
                                </div>
                                <div class="small text-muted mb-2" x-text="room.type_label + ' • ' + room.floor"></div>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="fw-semibold text-dark" x-text="'₹' + formatNum(room.rent_amount) + (room.rent_type === 'per_room' ? '/rm' : '/bed')"></span>
                                    <span class="text-muted" x-text="room.is_unit_type
                                        ? (room.active_occupants.length > 0 ? '1 Unit Booked' : 'Vacant Unit')
                                        : (room.occupied_count + '/' + room.capacity + ' Beds')">
                                    </span>
                                </div>
                                <div class="mt-2 pt-2 border-top small text-truncate"
                                     :class="room.active_occupants.length > 0 ? 'text-primary fw-medium' : 'text-success'">
                                    <i class="bi" :class="room.active_occupants.length > 0 ? 'bi-person-check-fill' : 'bi-person-plus'"></i>
                                    <span x-text="room.active_occupants.length > 0
                                        ? room.active_occupants.map(o => o.name).join(', ')
                                        : ('Click to add ' + room.occupant_term.toLowerCase())">
                                    </span>
                                </div>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Right: Selected Room Details Inspector -->
            <div class="col-lg-7">
                <template x-if="selectedRoom">
                    <div class="border rounded-3 p-3 p-md-4 bg-light-subtle h-100">
                        <!-- Selected Room Header -->
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pb-3 mb-3 border-bottom">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h5 class="fw-bold mb-0" x-text="'Room ' + selectedRoom.room_number"></h5>
                                    <span class="badge bg-secondary-subtle text-secondary border" x-text="selectedRoom.type_label"></span>
                                    <span class="badge bg-info-subtle text-info border" x-text="selectedRoom.rent_basis_label"></span>
                                </div>
                                <div class="small text-muted mt-1">
                                    <span x-text="selectedRoom.property_name"></span> &bull;
                                    <span x-text="selectedRoom.floor"></span> &bull;
                                    Rent: <strong x-text="'₹' + formatNum(selectedRoom.rent_amount)"></strong>
                                    (<span x-text="selectedRoom.rent_basis_label"></span>)
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button"
                                        x-show="selectedRoom.has_vacancy"
                                        @click="showAddForm = !showAddForm"
                                        class="btn btn-sm"
                                        :class="showAddForm ? 'btn-outline-secondary' : 'btn-success fw-semibold'">
                                    <i class="bi" :class="showAddForm ? 'bi-x-lg' : 'bi-person-plus-fill'"></i>
                                    <span x-text="showAddForm ? 'Close Add Form' : ('Add ' + selectedRoom.occupant_term)"></span>
                                </button>
                                <a :href="selectedRoom.room_show_url" class="btn btn-sm btn-light border">Room Page</a>
                            </div>
                        </div>

                        <!-- INLINE ADD PERSON FORM (for Vacant Rooms or Open Beds) -->
                        <div x-show="showAddForm" class="card border-success mb-3 shadow-sm">
                            <div class="card-header bg-success-subtle text-success fw-bold small d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-person-plus-fill me-1"></i> Add <span x-text="selectedRoom.occupant_term"></span> to Room <span x-text="selectedRoom.room_number"></span></span>
                                <span x-show="!selectedRoom.is_unit_type" x-text="selectedRoom.available_beds_count + ' free bed(s)'"></span>
                            </div>
                            <div class="card-body">
                                <form :action="storeUrl" method="POST">
                                    <input type="hidden" name="_token" :value="csrfToken">
                                    <input type="hidden" name="redirect_to" value="dashboard">
                                    <input type="hidden" name="property_id" :value="selectedRoom.property_id">
                                    <input type="hidden" name="room_id" :value="selectedRoom.id">

                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Full Name *</label>
                                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Full Name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Mobile Number (OTP Login) *</label>
                                            <input type="text" name="mobile" class="form-control form-control-sm" maxlength="15" placeholder="10-digit mobile" required>
                                        </div>
                                        <div class="col-md-6" x-show="!selectedRoom.is_unit_type">
                                            <label class="form-label small fw-semibold mb-1">Select Bed *</label>
                                            <select name="bed_id" class="form-select form-select-sm" :required="!selectedRoom.is_unit_type">
                                                <template x-for="bed in selectedRoom.available_beds" :key="bed.id">
                                                    <option :value="bed.id" x-text="'Bed ' + bed.label"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Joining Date *</label>
                                            <input type="date" name="joining_date" :value="today" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold mb-1">Monthly Rent (₹) *</label>
                                            <input type="number" name="monthly_rent" :value="selectedRoom.rent_amount" step="0.01" min="0" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold mb-1">Security Deposit (₹)</label>
                                            <input type="number" name="security_deposit" value="0" step="0.01" min="0" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold mb-1">Emergency Mobile</label>
                                            <input type="text" name="emergency_contact_mobile" class="form-control form-control-sm" maxlength="15">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Email (Optional)</label>
                                            <input type="email" name="email" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold mb-1">Address (Optional)</label>
                                            <input type="text" name="address" class="form-control form-control-sm">
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="submit" class="btn btn-success btn-sm fw-semibold px-3">
                                            <i class="bi bi-check-lg me-1"></i> Save &amp; Assign to Room
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- ACTIVE OCCUPANTS TABS -->
                        <div class="mb-3">
                            <div class="small fw-bold text-uppercase text-muted mb-2">
                                Currently Living in Room (<span x-text="selectedRoom.active_occupants.length"></span>)
                            </div>
                            <template x-if="selectedRoom.active_occupants.length === 0">
                                <div class="p-3 bg-white rounded-3 border text-muted small">
                                    No active <span x-text="selectedRoom.occupant_term.toLowerCase()"></span> in this room right now. Use the form above to add one.
                                </div>
                            </template>
                            <div class="d-flex flex-wrap gap-2" x-show="selectedRoom.active_occupants.length > 0">
                                <template x-for="occ in selectedRoom.active_occupants" :key="'act-' + occ.id">
                                    <button type="button" @click="selectPerson(occ)"
                                            class="btn btn-sm d-flex align-items-center gap-2"
                                            :class="selectedPerson && selectedPerson.id === occ.id ? 'btn-primary' : 'btn-white bg-white border'">
                                        <i class="bi bi-person-fill"></i>
                                        <span x-text="occ.name"></span>
                                        <span class="badge"
                                              :class="selectedPerson && selectedPerson.id === occ.id ? 'bg-white text-primary' : 'bg-light text-dark border'"
                                              x-text="occ.bed_label ? ('Bed ' + occ.bed_label) : 'Unit'"></span>
                                        <span x-show="occ.outstanding_due > 0" class="badge bg-danger">Due</span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- SELECTED PERSON FULL DETAILS (ACTIVE OR PAST) -->
                        <template x-if="selectedPerson">
                            <div class="bg-white rounded-3 border p-3 mb-3">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pb-2 mb-3 border-bottom">
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="fw-bold mb-0" x-text="selectedPerson.name"></h6>
                                            <span class="badge"
                                                  :class="selectedPerson.status === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'"
                                                  x-text="selectedPerson.status === 'active' ? 'Currently Living' : ('Left on ' + (selectedPerson.leaving_date || '-'))">
                                            </span>
                                            <span x-show="selectedPerson.bed_label" class="badge bg-light text-dark border" x-text="'Bed ' + selectedPerson.bed_label"></span>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-telephone me-1"></i><span x-text="selectedPerson.mobile"></span>
                                            <span x-show="selectedPerson.email"> &bull; <span x-text="selectedPerson.email"></span></span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a :href="selectedPerson.show_url" class="btn btn-sm btn-light border">Full Profile</a>
                                        <a :href="selectedPerson.edit_url" class="btn btn-sm btn-outline-primary">Edit</a>
                                        <form x-show="selectedPerson.status === 'active'" :action="selectedPerson.destroy_url" method="POST"
                                              @submit="if(!confirm('Remove ' + selectedPerson.name + ' from Room ' + selectedRoom.room_number + '? Their details will move to Left Earlier history.')) $event.preventDefault()">
                                            <input type="hidden" name="_token" :value="csrfToken">
                                            <input type="hidden" name="_method" value="DELETE">
                                            <input type="hidden" name="redirect_to" value="dashboard">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-person-dash me-1"></i> Remove <span x-text="selectedRoom.occupant_term"></span>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Personal Info + Security Deposit + Outstanding Summary -->
                                <div class="row g-2 mb-3 small">
                                    <div class="col-md-4">
                                        <div class="p-2 rounded bg-light h-100">
                                            <div class="text-muted">Personal &amp; Stay</div>
                                            <div><strong>Rent:</strong> ₹<span x-text="formatNum(selectedPerson.monthly_rent)"></span>/mo</div>
                                            <div><strong>Joined:</strong> <span x-text="selectedPerson.joining_date || '-'"></span></div>
                                            <div x-show="selectedPerson.leaving_date" class="text-danger"><strong>Left:</strong> <span x-text="selectedPerson.leaving_date"></span></div>
                                            <div><strong>Emergency:</strong> <span x-text="selectedPerson.emergency_contact_mobile || '-'"></span></div>
                                            <div x-show="selectedPerson.address"><strong>Address:</strong> <span x-text="selectedPerson.address"></span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-2 rounded bg-info-subtle bg-opacity-25 h-100">
                                            <div class="text-muted fw-semibold">Security Deposit</div>
                                            <div class="fs-6 fw-bold text-dark">Collected: ₹<span x-text="formatNum(selectedPerson.security_deposit.amount)"></span></div>
                                            <div>Balance Held: <strong>₹<span x-text="formatNum(selectedPerson.security_deposit.balance)"></span></strong></div>
                                            <div>
                                                Status:
                                                <span class="badge bg-info text-white text-uppercase" x-text="selectedPerson.security_deposit.status"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-2 rounded h-100" :class="selectedPerson.outstanding_due > 0 ? 'bg-danger-subtle' : 'bg-success-subtle'">
                                            <div class="text-muted fw-semibold">Total Rent Due</div>
                                            <div class="fs-5 fw-bold" :class="selectedPerson.outstanding_due > 0 ? 'text-danger' : 'text-success'">
                                                ₹<span x-text="formatNum(selectedPerson.outstanding_due)"></span>
                                            </div>
                                            <div x-text="selectedPerson.outstanding_due > 0 ? 'Payment Pending / Due' : 'All Dues Cleared'"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Monthly Rent & Dues Breakdown -->
                                <div class="mb-3">
                                    <div class="small fw-bold mb-1">Rent Records &amp; Dues</div>
                                    <div class="table-responsive" style="max-height: 160px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered mb-0 small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Total</th>
                                                    <th>Paid</th>
                                                    <th>Due</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-if="selectedPerson.rent_records.length === 0">
                                                    <tr><td colspan="6" class="text-center text-muted py-2">No rent records yet.</td></tr>
                                                </template>
                                                <template x-for="rec in selectedPerson.rent_records" :key="'rr-' + rec.id">
                                                    <tr>
                                                        <td x-text="rec.period"></td>
                                                        <td>₹<span x-text="formatNum(rec.total_amount)"></span></td>
                                                        <td class="text-success">₹<span x-text="formatNum(rec.paid_amount)"></span></td>
                                                        <td :class="rec.outstanding > 0 ? 'text-danger fw-bold' : 'text-muted'">
                                                            ₹<span x-text="formatNum(rec.outstanding)"></span>
                                                        </td>
                                                        <td>
                                                            <span class="badge"
                                                                  :class="rec.status === 'paid' ? 'bg-success' : (rec.status === 'partial' ? 'bg-warning text-dark' : 'bg-danger')"
                                                                  x-text="rec.status.toUpperCase()"></span>
                                                        </td>
                                                        <td>
                                                            <a :href="rec.show_url" class="btn btn-xs btn-outline-primary py-0 px-2" x-text="rec.outstanding > 0 ? 'Pay / View' : 'View'"></a>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Full Payment History -->
                                <div>
                                    <div class="small fw-bold mb-1">Payment History (<span x-text="selectedPerson.payments.length"></span>)</div>
                                    <div class="table-responsive" style="max-height: 160px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered mb-0 small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Receipt #</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Receipt</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-if="selectedPerson.payments.length === 0">
                                                    <tr><td colspan="6" class="text-center text-muted py-2">No payments recorded yet.</td></tr>
                                                </template>
                                                <template x-for="pay in selectedPerson.payments" :key="'pay-' + pay.id">
                                                    <tr>
                                                        <td class="fw-medium" x-text="pay.receipt_number"></td>
                                                        <td class="text-success fw-semibold">₹<span x-text="formatNum(pay.amount)"></span></td>
                                                        <td><span class="badge bg-light text-dark border" x-text="pay.method"></span></td>
                                                        <td x-text="pay.paid_at || '-'"></td>
                                                        <td><span class="badge bg-success-subtle text-success" x-text="pay.status"></span></td>
                                                        <td><a :href="pay.receipt_url" class="btn btn-xs btn-light border py-0 px-2">PDF</a></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- LEFT EARLIER / PAST OCCUPANTS -->
                        <div>
                            <div class="small fw-bold text-uppercase text-muted mb-2">
                                <i class="bi bi-clock-history me-1"></i>
                                Left Earlier / Past <span x-text="selectedRoom.occupant_term + 's'"></span>
                                (<span x-text="selectedRoom.past_occupants.length"></span>)
                            </div>
                            <template x-if="selectedRoom.past_occupants.length === 0">
                                <div class="small text-muted">No one has vacated this room yet.</div>
                            </template>
                            <div class="d-flex flex-wrap gap-2" x-show="selectedRoom.past_occupants.length > 0">
                                <template x-for="past in selectedRoom.past_occupants" :key="'past-' + past.id">
                                    <button type="button" @click="selectPerson(past)"
                                            class="btn btn-sm d-flex align-items-center gap-2"
                                            :class="selectedPerson && selectedPerson.id === past.id ? 'btn-dark' : 'btn-outline-secondary bg-white'">
                                        <i class="bi bi-person-x"></i>
                                        <span x-text="past.name"></span>
                                        <span class="small opacity-75" x-text="'(Left ' + (past.leaving_date || '-') + ')'"></span>
                                        <span x-show="past.outstanding_due > 0" class="badge bg-danger">Due ₹<span x-text="formatNum(past.outstanding_due)"></span></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif
</div>

<!-- Chart & Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="lm-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">6-Month Rent Collection Trend</h6>
                <a href="{{ route('owner.reports.rent-collection') }}" class="btn btn-sm btn-light">View Report</a>
            </div>
            <div style="height: 260px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="lm-card p-4 h-100">
            <h6 class="fw-bold mb-3">Quick Actions</h6>
            <div class="d-grid gap-2">
                <a href="{{ route('owner.properties.create') }}" class="btn btn-primary-subtle text-primary fw-semibold text-start border border-primary-subtle d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-building-add me-2"></i> Add Property & Rooms</span>
                    <span class="badge bg-primary text-white">2 Steps</span>
                </a>
                <a href="{{ route('owner.students.create') }}" class="btn btn-light text-start border">
                    <i class="bi bi-person-plus text-success me-2"></i> Onboard {{ $occupantTerm }}
                </a>
                <a href="{{ route('owner.rent.index') }}" class="btn btn-light text-start border">
                    <i class="bi bi-cash-coin text-warning me-2"></i> Record Rent Payment
                </a>
                <a href="{{ route('owner.electricity.create') }}" class="btn btn-light text-start border">
                    <i class="bi bi-lightning-charge text-info me-2"></i> Create Electricity Bill
                </a>
                <a href="{{ route('owner.expenses.create') }}" class="btn btn-light text-start border">
                    <i class="bi bi-wallet2 text-danger me-2"></i> Log Expense
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments & Recent Complaints -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="lm-card overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Recent Payments</h6>
                <a href="{{ route('owner.payments.index') }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>{{ $occupantTerm }}</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td class="fw-medium">{{ $payment->student_name }}</td>
                            <td class="text-success fw-semibold">₹{{ number_format($payment->amount, 2) }}</td>
                            <td><span class="badge bg-light text-dark border text-uppercase">{{ $payment->method }}</span></td>
                            <td class="text-muted small">{{ $payment->paid_at?->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="lm-card overflow-hidden">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Recent Complaints</h6>
                <a href="{{ route('owner.complaints.index') }}" class="small text-decoration-none">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Issue</th>
                            <th>{{ $occupantTerm }}</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentComplaints as $complaint)
                        <tr>
                            <td>
                                <a href="{{ route('owner.complaints.show', $complaint) }}" class="text-decoration-none fw-medium">
                                    {{ Str::limit($complaint->title, 30) }}
                                </a>
                            </td>
                            <td class="small">{{ $complaint->student?->name ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $complaint->priority === 'urgent' || $complaint->priority === 'high' ? 'danger' : 'secondary' }}-subtle text-{{ $complaint->priority === 'urgent' || $complaint->priority === 'high' ? 'danger' : 'secondary' }}">
                                    {{ ucfirst($complaint->priority) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'in_progress' ? 'info' : 'warning') }}">
                                    {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No complaints found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.dashboardRoomsBoard = function(config) {
    return {
        rooms: config.rooms || [],
        csrfToken: config.csrfToken,
        storeUrl: config.storeUrl,
        today: config.today,
        search: '',
        propertyFilter: '',
        statusFilter: 'all',
        selectedRoom: null,
        selectedPerson: null,
        showAddForm: false,

        init() {
            if (this.rooms.length > 0) {
                const target = config.initialRoomId
                    ? this.rooms.find(r => Number(r.id) === Number(config.initialRoomId))
                    : this.rooms[0];
                this.selectRoom(target || this.rooms[0]);
            }
        },

        get filteredRooms() {
            const q = this.search.trim().toLowerCase();
            return this.rooms.filter(r => {
                if (this.propertyFilter && String(r.property_id) !== String(this.propertyFilter)) {
                    return false;
                }
                if (this.statusFilter === 'available' && !r.has_vacancy) {
                    return false;
                }
                if (this.statusFilter === 'booked' && r.active_occupants.length === 0) {
                    return false;
                }
                if (!q) return true;
                const occNames = [...r.active_occupants, ...r.past_occupants].map(o => o.name.toLowerCase()).join(' ');
                return r.room_number.toLowerCase().includes(q)
                    || r.floor.toLowerCase().includes(q)
                    || r.type_label.toLowerCase().includes(q)
                    || occNames.includes(q);
            });
        },

        selectRoom(room) {
            this.selectedRoom = room;
            if (room.active_occupants.length > 0) {
                this.selectedPerson = room.active_occupants[0];
                this.showAddForm = false;
            } else {
                this.selectedPerson = room.past_occupants.length > 0 ? room.past_occupants[0] : null;
                this.showAddForm = room.has_vacancy;
            }
        },

        selectPerson(person) {
            this.selectedPerson = person;
            this.showAddForm = false;
        },

        formatNum(val) {
            const num = parseFloat(val || 0);
            return num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
};

const ctx = document.getElementById('revenueChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($monthlyRevenue)) !!},
            datasets: [{
                label: 'Rent Collected (₹)',
                data: {!! json_encode(array_values($monthlyRevenue)) !!},
                backgroundColor: 'rgba(79, 70, 229, 0.85)',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
}
</script>
@endpush
