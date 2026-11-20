@extends('layouts.app')

@section('title', 'Countries')

@section('content')
    <section>
        <h1>Countries</h1>
        @if($errors->any())
            <div class="notice error" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('countries-content')
    </section>
@endsection
