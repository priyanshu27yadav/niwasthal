<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\DepositTransaction;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Room;
use App\Models\SecurityDeposit;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $propertyIds = $this->ownerPropertyIds();

        $query = Student::whereIn('property_id', $propertyIds)
            ->with(['user', 'property', 'bed.room'])
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($propertyFilter = $request->input('property_id')) {
            $query->where('property_id', $propertyFilter);
        }

        if ($statusFilter = $request->input('status')) {
            $query->where('status', $statusFilter);
        }

        $students   = $query->paginate(15)->withQueryString();
        $properties = Property::where('owner_id', Auth::id())->get();

        return view('owner.students.index', compact('students', 'properties'));
    }

    public function create(Request $request)
    {
        $propertyIds = $this->ownerPropertyIds();
        $properties  = Property::whereIn('id', $propertyIds)
            ->with(['rooms' => fn ($q) => $q->with(['beds' => fn ($b) => $b->where('is_occupied', false)])])
            ->get();

        $properties->each(fn ($p) => $p->setRelation('rooms', Room::sortCollection($p->rooms)));

        return view('owner.students.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $propertyIds = $this->ownerPropertyIds();

        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'mobile'                   => 'required|string|max:15|unique:users,mobile',
            'email'                    => 'nullable|email|max:255|unique:users,email',
            'father_name'              => 'nullable|string|max:255',
            'property_id'              => 'required|integer|in:' . implode(',', $propertyIds ?: [0]),
            'room_id'                  => 'nullable|exists:rooms,id',
            'bed_id'                   => 'nullable|exists:beds,id',
            'joining_date'             => 'required|date',
            'monthly_rent'             => 'required|numeric|min:0',
            'security_deposit'         => 'nullable|numeric|min:0',
            'address'                  => 'nullable|string|max:500',
            'city'                     => 'nullable|string|max:100',
            'id_proof_type'            => 'nullable|string|max:50',
            'id_proof_number'          => 'nullable|string|max:100',
            'emergency_contact_name'   => 'nullable|string|max:255',
            'emergency_contact_mobile' => 'nullable|string|max:15',
            'notes'                    => 'nullable|string|max:1000',
        ]);

        $bed = null;
        $room = null;

        if (!empty($validated['bed_id'])) {
            $bed = Bed::with('room')->findOrFail($validated['bed_id']);
            if ((int) $bed->room->property_id !== (int) $validated['property_id']) {
                return back()->withInput()->withErrors(['bed_id' => 'Selected bed does not belong to the chosen property.']);
            }
            if ($bed->is_occupied) {
                return back()->withInput()->withErrors(['bed_id' => 'The selected bed is already occupied.']);
            }
            $room = $bed->room;
        } elseif (!empty($validated['room_id'])) {
            $room = Room::findOrFail($validated['room_id']);
            if ((int) $room->property_id !== (int) $validated['property_id']) {
                return back()->withInput()->withErrors(['room_id' => 'Selected room does not belong to the chosen property.']);
            }
            if (! $room->isUnitType()) {
                return back()->withInput()->withErrors(['bed_id' => 'Please select an available bed in this room.']);
            }
        } else {
            return back()->withInput()->withErrors(['bed_id' => 'Please select a room or bed.']);
        }

        $student = DB::transaction(function () use ($validated, $bed, $room) {
            $user = User::create([
                'name'     => $validated['name'],
                'mobile'   => $validated['mobile'],
                'email'    => $validated['email'] ?? null,
                'password' => Hash::make($validated['mobile']),
                'role'     => 'student',
                'owner_id' => Auth::id(),
            ]);

            $depositAmount = (float) ($validated['security_deposit'] ?? 0);

            $student = Student::create([
                'user_id'                  => $user->id,
                'property_id'              => $validated['property_id'],
                'bed_id'                   => $bed?->id,
                'room_id'                  => $room->id,
                'name'                     => $validated['name'],
                'mobile'                   => $validated['mobile'],
                'email'                    => $validated['email'] ?? null,
                'father_name'              => $validated['father_name'] ?? null,
                'address'                  => $validated['address'] ?? null,
                'city'                     => $validated['city'] ?? null,
                'id_proof_type'            => $validated['id_proof_type'] ?? null,
                'id_proof_number'          => $validated['id_proof_number'] ?? null,
                'emergency_contact_name'   => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_mobile' => $validated['emergency_contact_mobile'] ?? null,
                'joining_date'             => $validated['joining_date'],
                'monthly_rent'             => $validated['monthly_rent'],
                'security_deposit'         => $depositAmount,
                'status'                   => 'active',
                'notes'                    => $validated['notes'] ?? null,
            ]);

            if ($bed) {
                $bed->update(['is_occupied' => true]);
            }

            if ($depositAmount > 0) {
                $deposit = SecurityDeposit::create([
                    'student_id'   => $student->id,
                    'property_id'  => $validated['property_id'],
                    'student_name' => $student->name,
                    'amount'       => $depositAmount,
                    'balance'      => $depositAmount,
                    'collected_on' => $validated['joining_date'],
                    'status'       => 'held',
                ]);
                DepositTransaction::create([
                    'deposit_id'       => $deposit->id,
                    'type'             => 'collect',
                    'amount'           => $depositAmount,
                    'reason'           => 'Security deposit collected on joining.',
                    'transaction_date' => $validated['joining_date'],
                ]);
            }

            return $student;
        });

        $label = Auth::user()->occupantLabel($room->property);

        if ($request->input('redirect_to') === 'dashboard') {
            return redirect()
                ->route('owner.dashboard', ['room_id' => $room->id])
                ->with('success', "{$label} {$student->name} added to Room {$room->room_number}.");
        }

        return redirect()
            ->route('owner.students.show', $student)
            ->with('success', "{$label} {$student->name} added successfully. OTP Login Mobile: {$student->mobile}");
    }

    public function show(Student $student)
    {
        $this->authorizeStudent($student);

        $student->load([
            'user',
            'property',
            'room',
            'bed.room',
            'securityDeposit.transactions',
            'rentRecords' => fn ($q) => $q->orderByDesc('year')->orderByDesc('month'),
            'payments'    => fn ($q) => $q->orderByDesc('paid_at')->limit(15),
        ]);

        return view('owner.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorizeStudent($student);

        $beds = Bed::with('room')
            ->whereHas('room', fn ($q) => $q->where('property_id', $student->property_id))
            ->where(function ($q) use ($student) {
                $q->where('is_occupied', false)->orWhere('id', $student->bed_id);
            })
            ->get();

        return view('owner.students.edit', compact('student', 'beds'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudent($student);

        $validated = $request->validate([
            'name'                     => 'required|string|max:255',
            'mobile'                   => 'required|string|max:15|unique:users,mobile,' . $student->user_id,
            'email'                    => 'nullable|email|max:255|unique:users,email,' . $student->user_id,
            'bed_id'                   => 'nullable|exists:beds,id',
            'monthly_rent'             => 'required|numeric|min:0',
            'address'                  => 'nullable|string|max:500',
            'emergency_contact_mobile' => 'nullable|string|max:15',
            'notes'                    => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $student) {
            $oldBedId = $student->bed_id;
            $newBedId = $validated['bed_id'] ?? $oldBedId;

            if ($newBedId && (int) $newBedId !== (int) $oldBedId) {
                $newBed = Bed::with('room')->findOrFail($newBedId);
                if ((int) $newBed->room->property_id !== (int) $student->property_id || $newBed->is_occupied) {
                    abort(422, 'Selected bed is unavailable.');
                }
                if ($oldBedId) {
                    Bed::where('id', $oldBedId)->update(['is_occupied' => false]);
                }
                $newBed->update(['is_occupied' => true]);
            }

            $student->user->update([
                'name'   => $validated['name'],
                'mobile' => $validated['mobile'],
                'email'  => $validated['email'] ?? null,
            ]);

            $student->update([
                'name'                     => $validated['name'],
                'mobile'                   => $validated['mobile'],
                'email'                    => $validated['email'] ?? null,
                'room_id'                  => $newBedId ? $newBed->room_id : $student->room_id,
                'bed_id'                   => $newBedId,
                'monthly_rent'             => $validated['monthly_rent'],
                'address'                  => $validated['address'] ?? null,
                'emergency_contact_mobile' => $validated['emergency_contact_mobile'] ?? null,
                'notes'                    => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('owner.students.show', $student)
            ->with('success', 'Student record updated successfully.');
    }

    public function destroy(Request $request, Student $student)
    {
        $this->authorizeStudent($student);
        $roomId = $student->room_id ?? $student->bed?->room_id;

        DB::transaction(function () use ($student, $roomId) {
            if ($student->bed_id) {
                Bed::where('id', $student->bed_id)->update(['is_occupied' => false]);
            }

            $student->update([
                'status'       => 'vacated',
                'leaving_date' => now()->toDateString(),
                'room_id'      => $roomId,
                'bed_id'       => null,
            ]);
        });

        $label = Auth::user()->occupantLabel($student->property);

        if ($request->input('redirect_to') === 'dashboard') {
            return redirect()
                ->route('owner.dashboard', ['room_id' => $roomId])
                ->with('success', "{$label} {$student->name} removed and moved to past history.");
        }

        return redirect()
            ->route('owner.students.index')
            ->with('success', "{$label} {$student->name} has been vacated.");
    }

    public function generateRent(Student $student)
    {
        $this->authorizeStudent($student);
        $student->load(['room', 'bed.room']);

        $month = (int) now()->month;
        $year  = (int) now()->year;

        $exists = RentRecord::where('student_id', $student->id)
            ->where('month', $month)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('owner.students.show', $student)
                ->with('error', 'Rent record for ' . now()->format('F Y') . ' already exists.');
        }

        RentRecord::create([
            'student_id'   => $student->id,
            'property_id'  => $student->property_id,
            'student_name' => $student->name,
            'room_number'  => $student->room_number,
            'rent_amount'  => $student->monthly_rent,
            'late_fee'     => 0,
            'discount'     => 0,
            'total_amount' => $student->monthly_rent,
            'paid_amount'  => 0,
            'outstanding'  => $student->monthly_rent,
            'month'        => $month,
            'year'         => $year,
            'due_date'     => now()->startOfMonth()->addDays(4)->toDateString(),
            'status'       => 'unpaid',
        ]);

        return redirect()
            ->route('owner.students.show', $student)
            ->with('success', 'Rent record generated for ' . now()->format('F Y') . '.');
    }

    private function ownerPropertyIds(): array
    {
        return Property::where('owner_id', Auth::id())->pluck('id')->toArray();
    }

    private function authorizeStudent(Student $student): void
    {
        if (!in_array($student->property_id, $this->ownerPropertyIds())) {
            abort(403, 'Unauthorized access to this student record.');
        }
    }
}
