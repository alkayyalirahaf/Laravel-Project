# Flight selection handoff to Dev 4

The session key remains `dev3.search_selection`. This is the canonical output of
SearchSelectionService, written after POST /flights/selection by PublicFlightController.
Read it from the same Laravel web session in the booking flow. Do not trust a new
copy of the selection or its prices supplied by the browser.

## Required fields on every newly confirmed selection

| Field | Type / meaning |
| --- | --- |
| trip | string: one_way or round_trip |
| passengers | integer 1..9: total requested seats for each leg |
| passenger_counts.adults | integer 1..9 |
| passenger_counts.youth | integer 0..9 |
| passenger_counts.children | integer 0..9 |
| passenger_counts.infants | integer, currently always 0 |
| outbound.flight_id | integer: actual Flight ID |
| outbound.flight_class_id | integer: actual FlightClass ID, belonging to that flight |
| outbound.price | decimal string: per-seat base fare snapshot from the database |
| return | null for one_way; the same leg structure as outbound for round_trip |
| filters | validated search filters, including the four integer traveler counts |

The passenger breakdown applies to BOTH legs, not once per leg. Currently
`passengers = adults + youth + children`; infants must be zero until the team agrees
on infant seating rules. No infant price or lap-seat policy is assumed. Total seats
must not exceed 9. Conflicting explicitly submitted totals are rejected.

The search form submits flat `adults`, `youth`, `children`, `infants` fields, as does
the Landing form. The service validates and converts them to integers and builds
the canonical nested `passenger_counts` object on the SERVER. A browser-supplied
`passenger_counts` object cannot override these counts.

A legacy search specifying only `passengers` can still browse availability, but
cannot confirm a booking selection. It must collect the breakdown and search again;
we do not silently assume that everyone is an adult. The selection UI now collects
the breakdown explicitly. Older session selections missing `passenger_counts` must
be rejected/reselected by the booking flow, not guessed or silently upgraded.

## Dev 4 responsibilities

- Authenticate and authorize the customer and validate the entire session payload.
- Verify that all four counts are integers in range and that the seat total matches.
- Recheck each flight and class, their relationship, trip direction/dates, current fare,
  airline/flight status and current inventory before placing holds.
- Reload prices and create your own quote/taxes/fees; the saved price is not a final quote.
- Create holds atomically through the booking flow. Selection does not reserve seats.
- Collect date of birth and confirm category age boundaries and the mapping for youth
  with the team. Passenger.type currently has adult/child/infant, not youth. This
  contract preserves the youth count and does not invent a database mapping or fare rule.
- Implement the /book/passengers controller and consume the session contract. It is
  still a preview route; the selection page has not been redirected to it automatically.

No Models, Migrations, booking controller logic or seat inventory writes were changed
to establish this contract.
