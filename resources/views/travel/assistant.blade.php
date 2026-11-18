<x-travel-layout :title="$title">
    <h1>{{ $title }}</h1>
    <section class="panel prose-page">
        <h2>Travel planning</h2>
        <p>AI chat is currently unavailable. You can search, compare fares, or look up local tips directly.</p>
        <div class="actions">
            <a class="button" href="{{ route('flights.search') }}">Find flights</a>
            <a class="button secondary" href="{{ route('compare') }}">Compare</a>
            <a class="button secondary" href="{{ route('destinations') }}">Local tips</a>
        </div>
    </section>
</x-travel-layout>
