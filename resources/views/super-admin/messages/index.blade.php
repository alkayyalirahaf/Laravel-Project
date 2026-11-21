@extends('super-admin.management-layout')
@section('admin-title', 'Contact messages')
@section('admin-content')
    <h1>Contact messages</h1>
    <form method="GET" action="{{ route('admin.messages') }}" class="row g-2 mb-3">
        <div class="col-md-4"><label class="form-label" for="search">Search sender, email, or message</label><input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, or message text"></div>
        <div class="col-md-3"><label class="form-label" for="status">Status</label><select class="form-select" id="status" name="status"><option value="">All statuses</option><option value="new" @selected(($filters['status'] ?? '') === 'new')>New</option><option value="handled" @selected(($filters['status'] ?? '') === 'handled')>Handled</option></select></div>
        <div class="col-md-3"><label class="form-label" for="type">Type</label><select class="form-select" id="type" name="type"><option value="">All types</option><option value="suggestion" @selected(($filters['type'] ?? '') === 'suggestion')>Suggestion</option><option value="question" @selected(($filters['type'] ?? '') === 'question')>Question</option><option value="complaint" @selected(($filters['type'] ?? '') === 'complaint')>Complaint</option></select></div>
        <div class="col-md-2 d-flex align-items-end gap-2"><button class="btn btn-primary">Apply</button><a class="btn btn-outline-secondary" href="{{ route('admin.messages') }}">Clear</a></div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Sender</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>{{ $message->name }}</td>
                        <td>{{ $message->email }}</td>
                        <td>{{ $message->type }}</td>
                        <td style="min-width:250px">{{ $message->message }}</td>
                        <td>{{ $message->customer?->user?->name ?? 'Guest' }}</td>
                        <td>{{ $message->created_at }}</td>
                        <td>
                            @if($message->status === 'new')
                                <form method="POST" action="{{ route('admin.messages.handled', $message) }}">@csrf
                            @method('PATCH')<button class="btn btn-sm btn-primary">Mark handled</button></form>@else Handled
                                @endif
                        </td>
                </tr>@empty<tr>
                    <td colspan="7">No messages found.</td>
                </tr>@endforelse
            </tbody>
        </table>
    </div>{{ $messages->links('pagination::bootstrap-5') }}
@endsection
