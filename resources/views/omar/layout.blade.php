@extends('layouts.app')

@section('title', $title ?? 'Yalla Teer')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <section class="container py-4">
        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul></div>
        @endif
        @yield('omar-content')
    </section>
@endsection
