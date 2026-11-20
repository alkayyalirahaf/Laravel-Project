@extends('omar.layout', ['title' => 'Credit changes unavailable'])

@section('omar-content')
    <h1>Credit changes unavailable</h1>
    <p class="alert alert-warning">{{ $message }}</p>
    <a class="btn btn-secondary" href="{{ route('admin.credits') }}">Back to credit history</a>
@endsection
