@extends('layouts.student')

@section('title', 'My Complaints — Niwasthal')
@section('page-title', 'Complaints & Maintenance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Complaints</h4>
        <p class="text-muted small mb-0">Track the status of your maintenance & support requests.</p>
    </div>
    <a href="{{ route('student.complaints.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> New Complaint
    </a>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
                @forelse($complaints as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->title }}</td>
                    <td class="text-capitalize small">{{ $c->category }}</td>
                    <td><span class="badge bg-{{ in_array($c->priority, ['high','urgent']) ? 'danger' : 'secondary' }}-subtle text-{{ in_array($c->priority, ['high','urgent']) ? 'danger' : 'secondary' }}">{{ ucfirst($c->priority) }}</span></td>
                    <td><span class="badge bg-{{ $c->status === 'resolved' ? 'success' : ($c->status === 'in_progress' ? 'info' : 'warning') }}">{{ str_replace('_', ' ', ucfirst($c->status)) }}</span></td>
                    <td class="small text-muted">{{ $c->created_at?->format('d M Y') }}</td>
                    <td><a href="{{ route('student.complaints.show', $c) }}" class="btn btn-sm btn-light border">View</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No complaints submitted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $complaints->links() }}</div>
@endsection
