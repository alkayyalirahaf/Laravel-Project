@extends('super-admin.countries.layout')

@section('countries-content')
    <h2>Create country</h2>
    <form method="POST" action="{{ route('admin.countries.store') }}">
        @include('super-admin.countries.form')
    </form>
@endsection
