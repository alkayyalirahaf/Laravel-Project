@extends('super-admin.management-layout')
@section('admin-title', 'Notification logs')
@section('admin-content')
    <h1>Notification logs</h1>
    <form class="mb-3 d-flex gap-2" method="GET"><select class="form-select" name="status"><option value="">All statuses</option>@foreach(['pending','sent','failed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Booking</th><th>Channel</th><th>Type</th><th>Scheduled</th><th>Sent</th><th>Status</th></tr></thead><tbody>
        @forelse($logs as $log)<tr><td>{{ $log->booking->code }}</td><td>{{ $log->channel }}</td><td>{{ $log->type }}</td><td>{{ $log->scheduled_at }}</td><td>{{ $log->sent_at ?: '—' }}</td><td>{{ $log->status }}</td></tr>
        @empty<tr><td colspan="6">No notification logs found.</td></tr>@endforelse
    </tbody></table></div>{{ $logs->links('super-admin.pagination') }}
@endsection
