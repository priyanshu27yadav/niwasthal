@extends('layouts.student')

@section('title', 'Submit Complaint — Niwasthal')
@section('page-title', 'Submit Complaint')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="lm-card p-4">
            <h5 class="fw-bold mb-4">Report an Issue</h5>
            <form action="{{ route('student.complaints.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Issue Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="form-control" placeholder="e.g. Bathroom tap leaking" required>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category *</label>
                        <select name="category" class="form-select" required>
                            @foreach(['maintenance','cleanliness','noise','electricity','water','security','internet','other'] as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Priority *</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description *</label>
                    <textarea name="description" rows="4" class="form-control" placeholder="Describe the issue in detail..." required>{{ old('description') }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Attach Photos (Up to 3, Optional)</label>
                    <input type="file" name="photos[]" class="form-control" accept="image/*" multiple>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('student.complaints.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Complaint</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
