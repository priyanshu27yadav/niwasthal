<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    /**
     * Display a paginated listing of the owner's properties.
     */
    public function index()
    {
        $properties = Property::where('owner_id', Auth::id())
            ->withCount(['rooms', 'students' => function ($q) {
                $q->where('status', 'active');
            }])
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('owner.properties.index', compact('properties'));
    }

    /**
     * Show the form for creating a new property.
     */
    public function create()
    {
        return view('owner.properties.create');
    }

    /**
     * Store a newly created property in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'address'             => 'required|string|max:500',
            'city'                => 'required|string|max:100',
            'state'               => 'nullable|string|max:100',
            'pincode'             => 'nullable|string|max:10',
            'contact_number'      => 'nullable|string|max:15',
            'email'               => 'nullable|email|max:255',
            'type'                => 'nullable|in:pg,hostel,lodge',
            'total_floors'        => 'nullable|integer|min:1|max:20',
            'facilities'          => 'nullable|string|max:1000',
            'google_maps_url'     => 'nullable|url|max:500',
            'rooms'               => 'nullable|array',
            'rooms.*.room_number' => 'required|string|max:20|distinct',
            'rooms.*.floor'       => 'required|string|max:30',
            'rooms.*.type'        => 'required|in:single,double,triple,four_sharing,custom,family,shop',
            'rooms.*.capacity'    => 'nullable|integer|min:0|max:26',
            'rooms.*.rent_amount' => 'required|numeric|min:0',
            'rooms.*.rent_type'   => 'nullable|in:per_bed,per_room',
        ]);

        $roomsData = $validated['rooms'] ?? [];
        unset($validated['rooms']);

        $validated['owner_id']     = Auth::id();
        $validated['is_active']    = true;
        $validated['type']         = $validated['type'] ?? 'pg';
        $validated['total_floors'] = (int) ($validated['total_floors'] ?? 1);
        $validated['total_rooms']  = count($roomsData);

        $property = DB::transaction(function () use ($validated, $roomsData) {
            $property = Property::create($validated);

            foreach ($roomsData as $roomInput) {
                $isUnit = in_array($roomInput['type'], Room::UNIT_TYPES, true);
                $capacity = $isUnit ? 0 : max(1, (int) ($roomInput['capacity'] ?? 1));
                $rentType = $isUnit ? 'per_room' : ($roomInput['rent_type'] ?? 'per_bed');

                $room = Room::create([
                    'property_id' => $property->id,
                    'room_number' => trim($roomInput['room_number']),
                    'floor'       => trim($roomInput['floor']),
                    'type'        => $roomInput['type'],
                    'capacity'    => $capacity,
                    'rent_amount' => (float) $roomInput['rent_amount'],
                    'rent_type'   => $rentType,
                    'is_active'   => true,
                ]);

                if ($capacity > 0) {
                    $labels = range('A', 'Z');
                    for ($i = 0; $i < $capacity; $i++) {
                        Bed::create([
                            'room_id'     => $room->id,
                            'label'       => $labels[$i] ?? ('B' . ($i + 1)),
                            'is_occupied' => false,
                        ]);
                    }
                }
            }

            return $property;
        });

        $roomCount = count($roomsData);
        $message = $roomCount > 0
            ? 'Property "' . $property->name . '" and ' . $roomCount . ' room(s) created successfully!'
            : 'Property "' . $property->name . '" created successfully.';

        return redirect()
            ->route('owner.properties.show', $property)
            ->with('success', $message);
    }

    /**
     * Display the specified property with room and student summary.
     */
    public function show(Property $property)
    {
        $this->authorizeOwner($property);

        $property->loadCount([
            'rooms',
            'students' => fn ($q) => $q->where('status', 'active'),
        ]);

        $property->load([
            'rooms' => function ($q) {
                $q->withCount(['beds', 'beds as occupied_beds_count' => function ($q2) {
                    $q2->where('is_occupied', true);
                }]);
            },
        ]);

        $property->setRelation('rooms', Room::sortCollection($property->rooms));

        return view('owner.properties.show', compact('property'));
    }

    /**
     * Show the form for editing the specified property.
     */
    public function edit(Property $property)
    {
        $this->authorizeOwner($property);

        return view('owner.properties.edit', compact('property'));
    }

    /**
     * Update the specified property in the database.
     */
    public function update(Request $request, Property $property)
    {
        $this->authorizeOwner($property);

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'address'         => 'required|string|max:500',
            'city'            => 'required|string|max:100',
            'state'           => 'nullable|string|max:100',
            'pincode'         => 'nullable|string|max:10',
            'contact_number'  => 'nullable|string|max:15',
            'email'           => 'nullable|email|max:255',
            'type'            => 'nullable|in:pg,hostel,lodge',
            'facilities'      => 'nullable|string|max:1000',
            'google_maps_url' => 'nullable|url|max:500',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $property->update($validated);

        return redirect()
            ->route('owner.properties.show', $property)
            ->with('success', 'Property updated successfully.');
    }

    /**
     * Soft-delete the specified property.
     */
    public function destroy(Property $property)
    {
        $this->authorizeOwner($property);

        $activeStudents = $property->students()->where('status', 'active')->count();

        if ($activeStudents > 0) {
            return redirect()
                ->route('owner.properties.index')
                ->with('error', "Cannot delete property. There are {$activeStudents} active student(s) still assigned to it.");
        }

        $propertyName = $property->name;
        $property->delete(); // Soft delete (requires SoftDeletes on model)

        return redirect()
            ->route('owner.properties.index')
            ->with('success', "Property \"{$propertyName}\" has been deleted.");
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Ensure the authenticated owner owns this property.
     */
    private function authorizeOwner(Property $property): void
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'You do not have permission to access this property.');
        }
    }
}
