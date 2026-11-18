<x-travel-layout :title="$title">
    <section class="hero">
        <div>
            <p class="eyebrow">Flight reservations</p>
            <h1>Your next journey starts here.</h1>
            <p>Search routes, compare cabins, and explore your destination before you fly.</p>
            <a href="{{ route('destinations') }}">Explore destinations →</a>
        </div>
        <x-travel-search />
    </section>
    <section class="section">
        <h2>Plan your trip</h2>
        <div class="cards">
            <a class="panel tile" href="{{ route('flights.search') }}"><h3>Find a flight</h3><p>Browse upcoming routes and compare schedules.</p></a>
            <a class="panel tile" href="{{ route('destinations') }}"><h3>Destination guide</h3><p>Look up local languages, currencies, and travel tips.</p></a>
            <a class="panel tile" href="{{ route('booking.lookup') }}"><h3>Find your booking</h3><p>Use your booking code and last name.</p></a>
        </div>
    </section>
</x-travel-layout>
