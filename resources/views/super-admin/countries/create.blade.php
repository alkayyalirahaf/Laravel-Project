@extends('super-admin.countries.layout')

@section('countries-content')
    <h2 class="mb-3">Create country</h2>
    <form class="panel p-3 p-md-4" method="POST" action="{{ route('admin.countries.store') }}">
        @include('super-admin.countries.form')
    </form>
@endsection
