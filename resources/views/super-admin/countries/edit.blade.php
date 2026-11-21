@extends('super-admin.countries.layout')

@section('countries-content')
    <h2 class="mb-3">Edit {{ $country->name }}</h2>
    <form class="panel p-3 p-md-4" method="POST" action="{{ route('admin.countries.update', $country) }}">
        @method('PUT')
        @include('super-admin.countries.form')
    </form>
@endsection
