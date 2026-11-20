<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $ticket->ticket_number }} · Yalla Teer</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #17130e; color: #292017; font: 16px system-ui, sans-serif; }
        main { max-width: 680px; margin: auto; }
        nav { margin-bottom: 18px; display: flex; gap: 16px; align-items: center; }
        nav a { color: #f5d48d; }
        button { border: 0; border-radius: 8px; padding: 12px 16px; background: #f5d48d; color: #292017; font: inherit; cursor: pointer; }
        article { overflow: hidden; border: 1px solid #bfa06a; border-radius: 16px; background: #fffaf0; }
        header { padding: 24px; border-bottom: 3px solid #bfa06a; }
        section { padding: 24px; }
        h1 { margin: 5px 0; }
        p { margin: 6px 0; }
        dl { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 155px), 1fr)); gap: 16px; margin-bottom: 0; }
        dt { font-size: .85rem; }
        dd { margin: 3px 0 0; font-weight: 700; overflow-wrap: anywhere; }
        @media print {
            body { background: white; padding: 0; }
            nav { display: none; }
            article { break-inside: avoid; }
        }
    </style>
</head>
<body>
    @php($leg = $ticket->bookingFlight)
    @php($flight = $leg->flight)
    <main>
        <nav aria-label="Ticket actions">
            <a href="{{ route('my.booking', $leg->booking->code) }}">Back to booking</a>
            <button type="button" onclick="window.print()">Print / Save as PDF</button>
        </nav>
        <article>
            <header>
                <p>YALLA TEER</p>
                <h1>Electronic ticket</h1>
                <p>Booking reference {{ $leg->booking->code }}</p>
            </header>
            <section>
                <h2>{{ $ticket->passenger->first_name }} {{ $ticket->passenger->last_name }}</h2>
                <p>{{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}</p>
                <dl>
                    <div><dt>Ticket number</dt><dd>{{ $ticket->ticket_number }}</dd></div>
                    <div><dt>Flight</dt><dd>{{ $flight->flight_number }} · {{ $flight->airline->name }}</dd></div>
                    <div><dt>Departure</dt><dd>{{ $flight->departure_at->format('d M Y, H:i T') }}</dd></div>
                    <div><dt>Cabin</dt><dd>{{ ucfirst($leg->flightClass->class) }}</dd></div>
                    <div><dt>Seat</dt><dd>{{ $ticket->seat?->seat_number ?? 'Unassigned' }}</dd></div>
                </dl>
            </section>
        </article>
    </main>
</body>
</html>
