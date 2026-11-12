<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Notification;
use App\Models\Property;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $owner = Auth::user();
        $propertyIds = Property::where('owner_id', $owner->id)->pluck('id');

        $query = Complaint::with(['student', 'property', 'attachments'])
            ->whereIn('property_id', $propertyIds);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $complaints = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $properties = Property::where('owner_id', $owner->id)->get();

        return view('owner.complaints.index', compact('complaints', 'properties'));
    }

    public function show(Complaint $complaint)
    {
        $this->authorizeOwnership($complaint->property);
        $complaint->load(['student', 'property', 'attachments']);

        return view('owner.complaints.show', compact('complaint'));
    }

    public function updateStatus(Request $request, Complaint $complaint)
    {
        $this->authorizeOwnership($complaint->property);

        $validated = $request->validate([
            'status'         => 'required|in:pending,in_progress,resolved,rejected',
            'owner_response' => 'nullable|string|max:3000',
        ]);

        $previousStatus = $complaint->status;

        $updateData = [
            'status'         => $validated['status'],
            'owner_response' => $validated['owner_response'] ?? $complaint->owner_response,
        ];

        if ($validated['status'] === 'resolved' && $previousStatus !== 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $complaint->update($updateData);

        if ($complaint->student && $complaint->student->user_id) {
            $statusLabel = ucwords(str_replace('_', ' ', $validated['status']));
            Notification::create([
                'user_id' => $complaint->student->user_id,
                'type'    => 'complaint',
                'title'   => "Complaint Update: {$complaint->title}",
                'body'    => "Your complaint status is now {$statusLabel}." . (!empty($validated['owner_response']) ? " Response: {$validated['owner_response']}" : ''),
                'data'    => ['complaint_id' => $complaint->id],
            ]);

            try {
                app(WhatsAppService::class)->sendComplaintUpdate(
                    $complaint->student,
                    $complaint->title,
                    $statusLabel
                );
            } catch (\Throwable $e) {
                // Non-blocking WhatsApp notification
            }
        }

        return back()->with('success', 'Complaint status updated and student notified.');
    }

    public function destroy(Complaint $complaint)
    {
        $this->authorizeOwnership($complaint->property);
        $complaint->delete();

        return redirect()
            ->route('owner.complaints.index')
            ->with('success', 'Complaint deleted successfully.');
    }

    private function authorizeOwnership(Property $property): void
    {
        if ($property->owner_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
