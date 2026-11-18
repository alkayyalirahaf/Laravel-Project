@extends('layouts.app')

@section('title', 'Page not found')

@section('content')
<section class="panel prose-page"><p class="eyebrow">Error 404</p><h1>Page not found</h1><p>Check the address or search for a flight.</p><a class="button" href="{{ route('home') }}">Go to homepage</a></section>
<div class="section"><x-travel-search /></div>
@endsection
