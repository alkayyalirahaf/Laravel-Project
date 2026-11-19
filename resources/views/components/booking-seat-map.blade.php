@props(['rows', 'selected' => null])

<div class="table-wrap booking-cabin">
    <table>
        <caption>Sample cabin · Select an available seat</caption>
        <thead>
            <tr>
                <th scope="col"><span class="sr-only">Row</span></th>
                @foreach(['A', 'C', '', 'H', 'K'] as $column)
                    <th scope="col">@if($column){{ $column }}@else<span class="sr-only">Aisle</span>@endif</th>
                @endforeach
                <td></td>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row => $seats)
                <tr>
                    <th class="booking-cabin-edge" scope="row">{{ $row }}</th>
                    @foreach(['A', 'C', '', 'H', 'K'] as $column)
                        <td @class(['booking-cabin-aisle' => !$column])>
                            @if(isset($seats[$column]))
                                @php($seat = $row.$column)
                                <label class="booking-seat">
                                    <input class="sr-only" type="radio" name="seat" value="{{ $seat }}"
                                        @checked($selected === $seat) @disabled(!$seats[$column]) @required($seats[$column])
                                        aria-label="{{ 'Seat '.$seat.($seats[$column] ? '' : ', unavailable') }}">
                                    <span aria-hidden="true">@if(!$seats[$column])&times;@endif</span>
                                </label>
                            @endif
                        </td>
                    @endforeach
                    <td class="booking-cabin-edge" aria-hidden="true">{{ $row }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot aria-hidden="true">
            <tr><td></td><td>A</td><td>C</td><td></td><td>H</td><td>K</td><td></td></tr>
        </tfoot>
    </table>
    <div class="booking-cabin-legend">
        <span>Available</span><span>Selected</span><span>Unavailable</span>
    </div>
</div>
