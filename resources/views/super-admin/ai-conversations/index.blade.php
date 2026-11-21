@extends('super-admin.management-layout')
@section('admin-title', 'AI conversations')
@section('admin-content')
    <h1>AI conversations</h1>
    <form method="GET" action="{{ route('admin.ai-conversations') }}" class="row g-2 mb-3">
        <div class="col-md-6"><label class="form-label" for="search">Search title or customer</label><input class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Conversation title or customer name"></div>
        <div class="col-md-3"><label class="form-label" for="type">Conversation type</label><select class="form-select" id="type" name="type"><option value="">All types</option><option value="find" @selected(($filters['type'] ?? '') === 'find')>Find</option><option value="compare" @selected(($filters['type'] ?? '') === 'compare')>Compare</option><option value="tips" @selected(($filters['type'] ?? '') === 'tips')>Tips</option></select></div>
        <div class="col-md-3 d-flex align-items-end gap-2"><button class="btn btn-primary">Search / Filter</button><a class="btn btn-outline-secondary" href="{{ route('admin.ai-conversations') }}">Clear</a></div>
    </form>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Title</th><th>Type</th><th>Customer</th><th>Date</th><th></th></tr></thead><tbody>
        @forelse($conversations as $conversation)<tr><td>{{ $conversation->title }}</td><td>{{ ucfirst($conversation->type) }}</td><td>{{ $conversation->customer?->user?->name ?? 'Guest' }}</td><td>{{ $conversation->created_at }}</td><td><a href="{{ route('admin.ai-conversations.show', $conversation) }}">View</a></td></tr>
        @empty<tr><td colspan="5">No conversations found.</td></tr>@endforelse
    </tbody></table></div>{{ $conversations->links('pagination::bootstrap-5') }}
@endsection
