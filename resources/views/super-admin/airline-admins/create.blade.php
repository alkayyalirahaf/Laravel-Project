@extends('super-admin.management-layout')
@section('admin-title', 'Add airline admin')
@section('admin-content')
    <h1>Add airline admin</h1>
    <form method="POST" action="{{ route('admin.airline-admins.store') }}">@csrf
        @include('super-admin.airline-admins.form')
    </form>
@endsection
