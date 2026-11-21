@extends('super-admin.management-layout')
@section('admin-title', 'Airline admins')
@section('admin-content')
    <div class="d-flex justify-content-between mb-3"><h1>Airline admins</h1><a class="btn btn-primary" href="{{ route('admin.airline-admins.create') }}">Add admin</a></div>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Name</th><th>Email</th><th>Airline</th><th>Phone</th><th>Job title</th><th>Actions</th></tr></thead>
        <tbody>@forelse($airlineAdmins as $admin)
            <tr><td>{{ $admin->user->name }}</td><td>{{ $admin->user->email }}</td><td>{{ $admin->airline->name }}</td><td>{{ $admin->phone ?: '—' }}</td><td>{{ $admin->job_title ?: '—' }}</td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.airline-admins.edit', $admin) }}">Edit</a>
                    <form class="d-inline" method="POST" action="{{ route('admin.airline-admins.destroy', $admin) }}" onsubmit="return confirm('Delete this admin?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
        @empty<tr><td colspan="6">No airline admins found.</td></tr>@endforelse</tbody>
    </table></div>{{ $airlineAdmins->links('super-admin.pagination') }}
@endsection
