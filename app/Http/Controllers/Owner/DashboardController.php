<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Room;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the owner dashboard with key metrics and interactive live rooms board.
     */
    public function index(Request $request)
    {
        $owner = Auth::user();

        $properties = Property::where('owner_id', $owner->id)
            ->orderBy('name')
            ->get();

        $propertyIds = $properties->pluck('id')->toArray();

        $currentMonth = now()->month;
        $currentYear  = now()->year;

        // ── Occupants (Students / Renters) ───────────────────────────────────
        $totalActiveStudents = Student::whereIn('property_id', $propertyIds)
            ->where('status', 'active')
            ->count();

        // ── Rooms, Beds & Units ───────────────────────────────────────────────
        $totalRooms = Room::whereIn('property_id', $propertyIds)->count();

        // ── Rent Collected (current month) ────────────────────────────────────
        $rentCollected = RentRecord::whereIn('property_id', $propertyIds)
            ->where('status', 'paid')
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->sum('paid_amount');

        // ── Outstanding Dues (current month, unpaid + partial) ────────────────
        $outstandingDues = RentRecord::whereIn('property_id', $propertyIds)
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->sum('outstanding');

        // ── Pending Complaints ────────────────────────────────────────────────
        $pendingComplaints = Complaint::whereIn('property_id', $propertyIds)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        // ── Recent Payments (last 5) ──────────────────────────────────────────
        $recentPayments = Payment::whereIn('property_id', $propertyIds)
            ->with(['student', 'student.user'])
            ->orderByDesc('paid_at')
            ->limit(5)
            ->get();

        // ── Recent Complaints (last 5) ────────────────────────────────────────
        $recentComplaints = Complaint::whereIn('property_id', $propertyIds)
            ->with(['student', 'student.user'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // ── Monthly Revenue – last 6 months (for chart) ───────────────────────
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date       = now()->subMonths($i);
            $monthLabel = $date->format('M Y');
            $amount     = RentRecord::whereIn('property_id', $propertyIds)
                ->where('month', $date->month)
                ->where('year', $date->year)
                ->sum('paid_amount');

            $monthlyRevenue[$monthLabel] = (float) $amount;
        }

        // ── Interactive Live Rooms Board ──────────────────────────────────────
        $rooms = Room::sortCollection(
            Room::whereIn('property_id', $propertyIds)
                ->with([
                    'property',
                    'beds',
                    'activeStudents' => fn ($q) => $q->with([
                        'bed',
                        'rentRecords' => fn ($r) => $r->orderByDesc('year')->orderByDesc('month'),
                        'payments'    => fn ($p) => $p->orderByDesc('paid_at'),
                        'securityDeposit.transactions',
                    ])->orderBy('name'),
                    'pastStudents' => fn ($q) => $q->with([
                        'rentRecords' => fn ($r) => $r->orderByDesc('year')->orderByDesc('month'),
                        'payments'    => fn ($p) => $p->orderByDesc('paid_at'),
                        'securityDeposit.transactions',
                    ]),
                ])
                ->get()
        );

        // ── Occupancy Summary (Beds + Shop/Family Units) ──────────────────────
        $occupancy         = Room::summarizeOccupancy($rooms);
        $totalBeds         = $occupancy['totalBeds'];
        $occupiedBeds      = $occupancy['occupiedBeds'];
        $availableBeds     = $occupancy['totalBeds'] - $occupancy['occupiedBeds'];
        $totalUnits        = $occupancy['totalUnits'];
        $occupiedUnits     = $occupancy['occupiedUnits'];
        $totalCapacity     = $occupancy['totalCapacity'];
        $occupiedCapacity  = $occupancy['occupiedCapacity'];
        $availableCapacity = $occupancy['availableCapacity'];
        $occupancyPercent  = $occupancy['occupancyPercent'];

        $roomsBoardData = $rooms->map(function (Room $room) use ($owner) {
            $isUnit         = $room->isUnitType();
            $availableBeds  = $room->beds->where('is_occupied', false)->values();
            $occupiedCount  = $isUnit
                ? $room->activeStudents->count()
                : $room->beds->where('is_occupied', true)->count();
            $capacity       = $isUnit ? 1 : max(1, (int) $room->capacity);
            $isBooked       = $occupiedCount >= $capacity;

            return [
                'id'                   => $room->id,
                'property_id'          => $room->property_id,
                'property_name'        => $room->property?->name ?? '-',
                'property_type'        => $room->property?->type ?? 'pg',
                'occupant_term'        => $owner->occupantLabel($room->property),
                'room_number'          => $room->room_number,
                'floor'                => $room->floor ?? 'Ground Floor',
                'type'                 => $room->type,
                'type_label'           => ucfirst(str_replace('_', ' ', $room->type)),
                'is_unit_type'         => $isUnit,
                'capacity'             => (int) $room->capacity,
                'rent_amount'          => (float) $room->rent_amount,
                'rent_type'            => $room->rent_type ?? ($isUnit ? 'per_room' : 'per_bed'),
                'rent_basis_label'     => $room->rent_basis_label,
                'occupied_count'       => $occupiedCount,
                'available_beds_count' => $availableBeds->count(),
                'is_booked'            => $isBooked,
                'has_vacancy'          => $isUnit ? ($occupiedCount === 0) : ($availableBeds->count() > 0),
                'available_beds'       => $availableBeds->map(fn ($b) => [
                    'id'    => $b->id,
                    'label' => $b->label,
                ])->values(),
                'active_occupants'     => $room->activeStudents->map(
                    fn (Student $s) => $this->formatOccupantForBoard($s)
                )->values(),
                'past_occupants'       => $room->pastStudents->map(
                    fn (Student $s) => $this->formatOccupantForBoard($s)
                )->values(),
                'room_show_url'        => route('owner.properties.rooms.show', [$room->property_id, $room->id]),
            ];
        })->values();

        $selectedRoomId = (int) $request->input('room_id', 0);

        return view('owner.dashboard', compact(
            'properties',
            'roomsBoardData',
            'selectedRoomId',
            'totalActiveStudents',
            'totalRooms',
            'totalBeds',
            'occupiedBeds',
            'availableBeds',
            'totalUnits',
            'occupiedUnits',
            'totalCapacity',
            'occupiedCapacity',
            'availableCapacity',
            'rentCollected',
            'outstandingDues',
            'pendingComplaints',
            'recentPayments',
            'recentComplaints',
            'monthlyRevenue',
            'occupancyPercent'
        ));
    }

    private function formatOccupantForBoard(Student $student): array
    {
        $outstandingDue = (float) $student->rentRecords
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('outstanding');

        $deposit = $student->securityDeposit;

        return [
            'id'                       => $student->id,
            'name'                     => $student->name,
            'mobile'                   => $student->mobile,
            'email'                    => $student->email,
            'father_name'              => $student->father_name,
            'address'                  => $student->address,
            'city'                     => $student->city,
            'id_proof_type'            => $student->id_proof_type,
            'id_proof_number'          => $student->id_proof_number,
            'emergency_contact_name'   => $student->emergency_contact_name,
            'emergency_contact_mobile' => $student->emergency_contact_mobile,
            'joining_date'             => $student->joining_date?->format('d M Y'),
            'leaving_date'             => $student->leaving_date?->format('d M Y'),
            'monthly_rent'             => (float) $student->monthly_rent,
            'bed_label'                => $student->bed?->label,
            'status'                   => $student->status,
            'notes'                    => $student->notes,
            'outstanding_due'          => $outstandingDue,
            'security_deposit'         => $deposit ? [
                'id'           => $deposit->id,
                'amount'       => (float) $deposit->amount,
                'balance'      => (float) $deposit->balance,
                'status'       => $deposit->status,
                'collected_on' => $deposit->collected_on?->format('d M Y'),
                'transactions' => $deposit->transactions->map(fn ($t) => [
                    'type'             => $t->type,
                    'amount'           => (float) $t->amount,
                    'reason'           => $t->reason,
                    'transaction_date' => $t->transaction_date?->format('d M Y'),
                ])->values(),
            ] : [
                'id'           => null,
                'amount'       => (float) $student->security_deposit,
                'balance'      => (float) $student->security_deposit,
                'status'       => $student->security_deposit > 0 ? 'held' : 'none',
                'collected_on' => $student->joining_date?->format('d M Y'),
                'transactions' => [],
            ],
            'rent_records'             => $student->rentRecords->map(fn ($r) => [
                'id'           => $r->id,
                'period'       => date('M', mktime(0, 0, 0, $r->month, 1)) . ' ' . $r->year,
                'total_amount' => (float) $r->total_amount,
                'paid_amount'  => (float) $r->paid_amount,
                'outstanding'  => (float) $r->outstanding,
                'status'       => $r->status,
                'due_date'     => $r->due_date?->format('d M Y'),
                'show_url'     => route('owner.rent.show', $r->id),
            ])->values(),
            'payments'                 => $student->payments->map(fn ($p) => [
                'id'             => $p->id,
                'receipt_number' => $p->receipt_number,
                'amount'         => (float) $p->amount,
                'method'         => strtoupper($p->method ?? 'cash'),
                'status'         => $p->status,
                'paid_at'        => $p->paid_at?->format('d M Y'),
                'notes'          => $p->notes,
                'receipt_url'    => route('owner.payments.receipt', $p->id),
            ])->values(),
            'show_url'                 => route('owner.students.show', $student->id),
            'edit_url'                 => route('owner.students.edit', $student->id),
            'destroy_url'              => route('owner.students.destroy', $student->id),
            'generate_rent_url'        => route('owner.students.generate-rent', $student->id),
        ];
    }
}
