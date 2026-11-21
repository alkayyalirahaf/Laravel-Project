@extends('super-admin.management-layout')
@section('admin-title', 'Notification logs')
@section('admin-content')
    <h1>Notification logs</h1>
    <form class="row g-2 mb-3" method="GET" action="{{ route('admin.notifications') }}">
        <div class="col-md-4"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option>@foreach(['pending', 'sent', 'failed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-auto d-flex align-items-end gap-2"><button class="btn btn-primary">Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.notifications') }}">Clear</a></div>
    </form>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Booking</th><th>Channel</th><th>Type</th><th>Scheduled</th><th>Sent</th><th>Status</th></tr></thead><tbody>
        @forelse($logs as $log)<tr><td>{{ $log->booking->code }}</td><td>{{ $log->channel }}</td><td>{{ $log->type }}</td><td>{{ $log->scheduled_at }}</td><td>{{ $log->sent_at ?: '—' }}</td><td>{{ $log->status }}</td></tr>
        @empty<tr><td colspan="6">No notification logs found.</td></tr>@endforelse
    </tbody></table></div>{{ $logs->links('super-admin.pagination') }}
@endsection
