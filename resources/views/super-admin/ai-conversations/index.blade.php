@extends('super-admin.management-layout')
@section('admin-title', 'AI conversations')
@section('admin-content')
    <h1>AI conversations</h1>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Title</th><th>Type</th><th>Customer</th><th>Date</th><th></th></tr></thead><tbody>
        @forelse($conversations as $conversation)<tr><td>{{ $conversation->title }}</td><td>{{ ucfirst($conversation->type) }}</td><td>{{ $conversation->customer?->user?->name ?? 'Guest' }}</td><td>{{ $conversation->created_at }}</td><td><a href="{{ route('admin.ai-conversations.show', $conversation) }}">View</a></td></tr>
        @empty<tr><td colspan="5">No conversations found.</td></tr>@endforelse
    </tbody></table></div>{{ $conversations->links('pagination::bootstrap-5') }}
@endsection
