@extends('omar.layout', ['title' => 'Saved conversation'])

@section('omar-content')
    <h1>{{ $conversation->title }}</h1>
    <p class="card card-body">{{ $transcript['message'] }}</p>
    <h2>Travel tips</h2>
    <ul>@foreach($transcript['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul>
    @if($conversation->type !== 'tips' && empty($transcript['flights']))
        <p class="alert alert-info">No database flights matched your filters.</p>
    @endif
    @if(!empty($transcript['flights']))
        <h2>Database flight results</h2>
        <p>Prices and availability recorded at {{ $transcript['recorded_at'] }}. Times use {{ $transcript['timezone'] }}. Search again for current availability.</p>
        <div class="table-responsive"><table class="table table-bordered">
            <thead><tr><th>Flight</th><th>Airline</th><th>Route and times</th><th>Cabins, base fares and baggage</th></tr></thead><tbody>
            @foreach($transcript['flights'] as $flight)
                <tr><td><a href="{{ route('flights.show', $flight['id']) }}">{{ $flight['number'] }} (#{{ $flight['id'] }})</a></td><td>{{ $flight['airline'] }}</td><td>{{ $flight['from'] }} → {{ $flight['to'] }}<br>{{ $flight['departure'] }} → {{ $flight['arrival'] }}</td><td>
                    @foreach($flight['cabins'] as $class)
                        <p>{{ ucfirst($class['class']) }} — {{ $class['price'] }}<br>Cabin {{ $class['cabin_baggage_kg'] }} kg; checked {{ $class['checked_baggage_kg'] }} kg<br>{{ $class['available_seats'] }} seats available at search time</p>
                    @endforeach
                </td></tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
    <a class="btn btn-primary" href="{{ route('my.assistant') }}">Back to assistant</a>
@endsection
