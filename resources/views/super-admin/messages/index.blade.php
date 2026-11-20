@extends('super-admin.management-layout')
@section('admin-title', 'Contact messages')
@section('admin-content')
    <h1>Contact messages</h1>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Sender</th><th>Email</th><th>Type</th><th>Message</th><th>Customer</th><th>Date</th><th>Status</th></tr></thead><tbody>
        @forelse($messages as $message)<tr><td>{{ $message->name }}</td><td>{{ $message->email }}</td><td>{{ $message->type }}</td><td style="min-width:250px">{{ $message->message }}</td><td>{{ $message->customer?->user?->name ?? 'Guest' }}</td><td>{{ $message->created_at }}</td><td>
            @if($message->status === 'new')<form method="POST" action="{{ route('admin.messages.handled', $message) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-primary">Mark handled</button></form>@else Handled @endif
        </td></tr>@empty<tr><td colspan="7">No messages found.</td></tr>@endforelse
    </tbody></table></div>{{ $messages->links('pagination::bootstrap-5') }}
@endsection
