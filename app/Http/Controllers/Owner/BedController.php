<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;

class BedController extends Controller
{
    public function index(Property $property)
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this property.');
        }

        $rooms = Room::sortCollection($property->rooms()->with('beds.student.user')->get());

        return view('owner.beds.index', compact('property', 'rooms'));
    }

    public function toggle(Property $property, Bed $bed)
    {
        if ($property->owner_id !== Auth::id() || $bed->room->property_id !== $property->id) {
            abort(403, 'Unauthorized access to this bed.');
        }

        if ($bed->student && $bed->student->status === 'active') {
            return back()->with('error', 'Cannot manually toggle a bed with an active student.');
        }

        $bed->update(['is_occupied' => !$bed->is_occupied]);

        return back()->with('success', 'Bed status updated.');
    }
}
