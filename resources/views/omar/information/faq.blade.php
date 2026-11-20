@extends('omar.layout', ['title' => 'Frequently asked questions'])

@section('omar-content')
    <h1>Frequently asked questions</h1>
    @foreach([
        'How do I search for a flight?' => 'Choose departure and arrival airports or cities, a travel date and the seats needed. You can filter by cabin and base fare and sort the results.',
        'How do I search for a round trip?' => 'Choose round trip and enter both dates. Select an outbound cabin, then select a return cabin from the matching results.',
        'Does selecting a flight reserve a seat?' => 'Search selection does not reserve seats. Availability and prices must be confirmed by the booking process.',
        'Where can I see baggage information?' => 'Flight details and comparison show the cabin and checked baggage weights recorded for each cabin.',
        'How many flights can I compare?' => 'Select two or three different flights in the search results.',
        'When can I submit a review?' => 'You can review a flight segment belonging to your own completed booking after that flight has completed. Each segment can be reviewed once.',
        'What are the cancellation and refund rules?' => 'Approved cancellation, refund and credit policies have not been supplied for publication. Contact the platform team for the applicable terms before booking.',
    ] as $question => $answer)
        <details class="card card-body mb-3"><summary>{{ $question }}</summary><p class="mt-2 mb-0">{{ $answer }}</p></details>
    @endforeach
@endsection
