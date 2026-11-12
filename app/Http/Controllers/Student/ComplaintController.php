<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student()->firstOrFail();

        $complaints = Complaint::with('attachments')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('student.complaints.index', compact('student', 'complaints'));
    }

    public function create(): View
    {
        $student = auth()->user()->student()->firstOrFail();
        return view('student.complaints.create', compact('student'));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = auth()->user()->student()->with('property')->firstOrFail();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|in:maintenance,cleanliness,noise,electricity,water,security,internet,other',
            'priority'    => 'required|in:low,medium,high,urgent',
            'description' => 'required|string|max:2000',
            'photos.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $complaint = DB::transaction(function () use ($student, $validated, $request) {
            $complaint = Complaint::create([
                'student_id'  => $student->id,
                'property_id' => $student->property_id,
                'title'       => $validated['title'],
                'category'    => $validated['category'],
                'priority'    => $validated['priority'],
                'description' => $validated['description'],
                'status'      => 'pending',
            ]);

            if ($request->hasFile('photos')) {
                foreach (array_slice($request->file('photos'), 0, 3) as $photo) {
                    $path = $photo->store('complaints/' . $complaint->id, 'public');
                    ComplaintAttachment::create([
                        'complaint_id'  => $complaint->id,
                        'path'          => $path,
                        'mime_type'     => $photo->getClientMimeType(),
                        'original_name' => $photo->getClientOriginalName(),
                    ]);
                }
            }

            if ($student->property && $student->property->owner_id) {
                Notification::create([
                    'user_id' => $student->property->owner_id,
                    'type'    => 'complaint',
                    'title'   => 'New Complaint: ' . $complaint->title,
                    'body'    => "{$student->name} submitted a {$complaint->priority} priority complaint.",
                    'data'    => ['complaint_id' => $complaint->id],
                ]);
            }

            return $complaint;
        });

        return redirect()->route('student.complaints.show', $complaint)
            ->with('success', 'Complaint submitted successfully!');
    }

    public function show(Complaint $complaint): View
    {
        $student = auth()->user()->student()->firstOrFail();

        if ($complaint->student_id !== $student->id) {
            abort(403, 'Unauthorized access to complaint.');
        }

        $complaint->load(['attachments', 'property']);

        return view('student.complaints.show', compact('student', 'complaint'));
    }
}
