Yalla Teer e-tickets
Booking reference {{ $booking->code }}

@foreach($booking->passengers as $passenger)
@continue($passenger->tickets->isEmpty())
Passenger: {{ $passenger->first_name }} {{ $passenger->last_name }}
@foreach($passenger->tickets->sortBy(fn ($ticket) => $ticket->bookingFlight->flight->departure_at) as $ticket)
@php($leg = $ticket->bookingFlight)
@php($flight = $leg->flight)
{{ ucfirst($leg->direction) }} · {{ $flight->flight_number }} · {{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}
Departure: {{ $flight->departure_at->format('d M Y, H:i T') }}
Cabin: {{ ucfirst($leg->flightClass->class) }} · Seat: {{ $ticket->seat?->seat_number ?? 'Unassigned' }}
Ticket number: {{ $ticket->ticket_number }}
@endforeach

@endforeach
