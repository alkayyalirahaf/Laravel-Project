@extends('super-admin.management-layout')
@section('admin-title', 'Customers')
@section('admin-content')
    <h1>Customers</h1>
    <form class="mb-3 d-flex gap-2 align-items-end" method="GET" action="{{ route('admin.customers') }}"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name or email"><button class="btn btn-primary" type="submit">Search</button></form>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Bookings</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($customers as $customer)<tr><td>{{ $customer->user->name }}</td><td>{{ $customer->user->email }}</td><td>{{ $customer->phone ?: '—' }}</td><td>{{ $customer->bookings_count }}</td><td>{{ $customer->is_banned ? 'Banned' : 'Active' }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.customers.show', $customer) }}">Details</a></td></tr>
        @empty<tr><td colspan="6">No customers found.</td></tr>@endforelse</tbody></table></div>
    {{ $customers->links('super-admin.pagination') }}
@endsection
