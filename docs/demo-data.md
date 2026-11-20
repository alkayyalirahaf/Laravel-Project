# Local demo data

Run `php artisan db:seed` in local/testing, or explicitly run
`php artisan db:seed --class=DemoSeeder`. Production retains AuthSeeder and
its environment-configured credentials; DemoSeeder refuses production.

All demo accounts use password `FlyNow-Demo-2026!`:

| Role | Email |
| --- | --- |
| Platform administrator | admin@demo.example.test |
| Airline staff | staff.rj@demo.example.test |
| Airline staff | staff.ek@demo.example.test |
| Airline staff | staff.tk@demo.example.test |
| Customer | traveler@demo.example.test |
| Family customer | family@demo.example.test |
| Business customer | business@demo.example.test |
| New traveler | new@demo.example.test |

Includes 12 destinations, 5 airline records, and 462 flights with economy,
business, and first class. Search AMM–DXB tomorrow or in 3, 7, 14, 21, or
30 days. Return flights depart four days later. Three airlines on each
route/date allow comparison. Past completed, delayed, and cancelled flights
are included. Schedules and fares are fictional test fixtures.

Each customer has confirmed, completed, cancelled, and pending bookings.
Family bookings include two adults and a child on both legs. Paid bookings
have tickets and payment records; completed bookings include reviews.
Pending bookings have ordinary 15-minute seat holds from the initial seed.
After expiry, create a new booking through the normal flow.

Also includes credit opening balances with ledger entries, support messages,
historical notification logs, saved fictional AI conversations and recommendations.
No messages are sent and no payment or AI provider is called.

Re-running on the same date does not duplicate fixtures or reset edits,
passwords, seats, or balances. Running on a later date adds flights for the
new date window while retaining existing bookings and flights. No records
are truncated or deleted. Existing matching destination/airline records are reused;
missing destination guide fields are filled. The existing BrowserFlowSeeder
and any browser-created bookings remain intact.
