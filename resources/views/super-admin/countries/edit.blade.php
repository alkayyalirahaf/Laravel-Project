@extends('super-admin.countries.layout')

@section('countries-content')
    <h2>Edit {{ $country->name }}</h2>
    <form method="POST" action="{{ route('admin.countries.update', $country) }}">
        @method('PUT')
        @include('super-admin.countries.form')
    </form>
@endsection
