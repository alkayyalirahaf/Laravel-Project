@extends('super-admin.management-layout')
@section('admin-title', 'AI conversation details')
@section('admin-content')
    <h1>{{ $conversation->title }}</h1>
    <p>Type: {{ $conversation->type }} | Customer: {{ $conversation->customer?->user?->name ?? 'Guest' }} | Created: {{ $conversation->created_at }}</p>
    <h2>Recommendations</h2>
    <ul>@forelse($conversation->recommendations as $recommendation)<li>{{ $recommendation->flight?->airline?->name }} {{ $recommendation->flight?->flight_number }} — {{ $recommendation->reason }}</li>@empty<li>No recommendations recorded.</li>@endforelse</ul>
    <a href="{{ route('admin.ai-conversations') }}">Back to conversations</a>
@endsection
