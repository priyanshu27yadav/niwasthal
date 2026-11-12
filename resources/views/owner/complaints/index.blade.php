@extends('layouts.owner')

@section('title', 'Complaints — Niwasthal')
@section('page-title', 'Complaints & Maintenance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Complaints</h4>
        <p class="text-muted small mb-0">Review and resolve resident maintenance requests.</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="{{ route('owner.complaints.index') }}" class="btn btn-{{ !request('status') ? 'primary' : 'outline-secondary' }}">All</a>
        <a href="{{ route('owner.complaints.index', ['status' => 'pending']) }}" class="btn btn-{{ request('status') === 'pending' ? 'primary' : 'outline-secondary' }}">Pending</a>
        <a href="{{ route('owner.complaints.index', ['status' => 'in_progress']) }}" class="btn btn-{{ request('status') === 'in_progress' ? 'primary' : 'outline-secondary' }}">In Progress</a>
        <a href="{{ route('owner.complaints.index', ['status' => 'resolved']) }}" class="btn btn-{{ request('status') === 'resolved' ? 'primary' : 'outline-secondary' }}">Resolved</a>
    </div>
</div>

<div class="lm-card overflow-hidden">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr><th>Title</th><th>Student</th><th>Category</th><th>Priority</th><th>Status</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
                @forelse($complaints as $complaint)
                <tr>
                    <td class="fw-semibold">{{ $complaint->title }}</td>
                    <td>{{ $complaint->student?->name ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark border text-capitalize">{{ $complaint->category }}</span></td>
                    <td>
                        <span class="badge bg-{{ in_array($complaint->priority, ['high', 'urgent']) ? 'danger' : 'secondary' }}-subtle text-{{ in_array($complaint->priority, ['high', 'urgent']) ? 'danger' : 'secondary' }}">
                            {{ ucfirst($complaint->priority) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $complaint->status === 'resolved' ? 'success' : ($complaint->status === 'in_progress' ? 'info' : ($complaint->status === 'rejected' ? 'danger' : 'warning')) }}">
                            {{ str_replace('_', ' ', ucfirst($complaint->status)) }}
                        </span>
                    </td>
                    <td class="small text-muted">{{ $complaint->created_at?->format('d M Y') }}</td>
                    <td><a href="{{ route('owner.complaints.show', $complaint) }}" class="btn btn-sm btn-primary">Manage</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No complaints found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $complaints->links() }}</div>
@endsection
