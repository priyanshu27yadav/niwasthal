<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Room;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request, Property $property)
    {
        $this->authorizeOwner($property);

        $sorted = Room::sortCollection(
            Room::where('property_id', $property->id)
                ->withCount([
                    'beds',
                    'beds as occupied_beds_count' => fn ($q) => $q->where('is_occupied', true),
                    'beds as available_beds_count' => fn ($q) => $q->where('is_occupied', false),
                ])
                ->with('beds')
                ->get()
        );

        $perPage = 20;
        $page    = max(1, (int) $request->input('page', 1));
        $rooms   = new LengthAwarePaginator(
            $sorted->forPage($page, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('owner.rooms.index', compact('property', 'rooms'));
    }

    public function create(Property $property)
    {
        $this->authorizeOwner($property);

        return view('owner.rooms.create', compact('property'));
    }

    public function store(Request $request, Property $property)
    {
        $this->authorizeOwner($property);

        $validated = $this->validateRoomRequest($request);

        $exists = Room::where('property_id', $property->id)
            ->where('room_number', $validated['room_number'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['room_number' => 'Room number already exists in this property.']);
        }

        $validated = $this->normalizeRoomAttributes($validated);
        $validated['property_id'] = $property->id;
        $validated['is_active']   = true;

        DB::transaction(function () use ($validated, $property) {
            $room = Room::create($validated);
            if ($validated['capacity'] > 0) {
                $this->createBeds($room, (int) $validated['capacity']);
            }
            $property->update(['total_rooms' => $property->rooms()->count()]);
        });

        $msg = $validated['capacity'] > 0
            ? "Room {$validated['room_number']} created with {$validated['capacity']} bed(s)."
            : "Unit {$validated['room_number']} (" . ucfirst($validated['type']) . ") created with Per Room pricing.";

        return redirect()
            ->route('owner.properties.rooms.index', $property)
            ->with('success', $msg);
    }

    public function show(Property $property, Room $room)
    {
        $this->authorizeOwner($property);
        $this->authorizeRoomBelongsToProperty($room, $property);

        $room->load(['beds.student.user']);

        return view('owner.rooms.show', compact('property', 'room'));
    }

    public function edit(Property $property, Room $room)
    {
        $this->authorizeOwner($property);
        $this->authorizeRoomBelongsToProperty($room, $property);

        return view('owner.rooms.edit', compact('property', 'room'));
    }

    public function update(Request $request, Property $property, Room $room)
    {
        $this->authorizeOwner($property);
        $this->authorizeRoomBelongsToProperty($room, $property);

        $validated = $this->validateRoomRequest($request);

        $exists = Room::where('property_id', $property->id)
            ->where('room_number', $validated['room_number'])
            ->where('id', '!=', $room->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['room_number' => 'Room number already exists in this property.']);
        }

        $validated = $this->normalizeRoomAttributes($validated);

        if ($validated['capacity'] === 0 && $room->occupied_beds_count > 0) {
            return back()
                ->withInput()
                ->withErrors(['type' => 'Cannot change to Shop/Family unit while beds in this room are occupied.']);
        }

        $oldCapacity = $room->capacity;
        $newCapacity = (int) $validated['capacity'];

        DB::transaction(function () use ($room, $validated, $oldCapacity, $newCapacity) {
            $room->update($validated);

            if ($newCapacity === 0) {
                $room->beds()->delete();
            } elseif ($newCapacity > $oldCapacity) {
                $this->createBeds($room, $newCapacity - $oldCapacity, $oldCapacity);
            }
        });

        return redirect()
            ->route('owner.properties.rooms.show', [$property, $room])
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(Property $property, Room $room)
    {
        $this->authorizeOwner($property);
        $this->authorizeRoomBelongsToProperty($room, $property);

        $activeStudentsCount = Student::whereHas('bed', fn ($q) => $q->where('room_id', $room->id))
            ->where('status', 'active')
            ->count();

        if ($activeStudentsCount > 0) {
            return redirect()
                ->route('owner.properties.rooms.index', $property)
                ->with('error', "Cannot delete room {$room->room_number}. It has {$activeStudentsCount} active student(s).");
        }

        $roomNumber = $room->room_number;

        DB::transaction(function () use ($room, $property) {
            $room->beds()->delete();
            $room->delete();
            $property->update(['total_rooms' => $property->rooms()->count()]);
        });

        return redirect()
            ->route('owner.properties.rooms.index', $property)
            ->with('success', "Room {$roomNumber} and its beds have been deleted.");
    }

    private function validateRoomRequest(Request $request): array
    {
        return $request->validate([
            'room_number' => 'required|string|max:20',
            'floor'       => 'nullable|string|max:20',
            'type'        => 'required|in:single,double,triple,four_sharing,custom,family,shop',
            'capacity'    => 'required_unless:type,family,shop|nullable|integer|min:0|max:26',
            'rent_amount' => 'required|numeric|min:0',
            'rent_type'   => 'nullable|in:per_bed,per_room',
            'description' => 'nullable|string|max:500',
        ]);
    }

    private function normalizeRoomAttributes(array $validated): array
    {
        if (in_array($validated['type'], Room::UNIT_TYPES, true)) {
            $validated['capacity']  = 0;
            $validated['rent_type'] = 'per_room';
        } else {
            $validated['capacity']  = max(1, (int) ($validated['capacity'] ?? 1));
            $validated['rent_type'] = $validated['rent_type'] ?? 'per_bed';
        }

        return $validated;
    }

    private function createBeds(Room $room, int $count, int $offset = 0): void
    {
        $labels = range('A', 'Z');

        for ($i = 0; $i < $count; $i++) {
            $labelIndex = $offset + $i;
            $label      = $labelIndex < count($labels) ? $labels[$labelIndex] : (string) ($labelIndex + 1);

            Bed::firstOrCreate(
                ['room_id' => $room->id, 'label' => $label],
                ['is_occupied' => false]
            );
        }
    }

    private function authorizeOwner(Property $property): void
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this property.');
        }
    }

    private function authorizeRoomBelongsToProperty(Room $room, Property $property): void
    {
        if ($room->property_id !== $property->id) {
            abort(404, 'Room not found in this property.');
        }
    }
}
