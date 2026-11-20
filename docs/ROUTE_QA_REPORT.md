# Route QA — 2026-11-20

81 registered routes: 61 support GET and 20 are separate mutation endpoints. AllRoutesTest dispatches every GET with the appropriate role, every protected route as a guest and wrong role, and every mutation endpoint with invalid input (or its expected redirect/signature behavior). The new matrix passed 190 assertions. Domain feature tests separately cover successful CRUD, authentication, search, reviews, credits and mocked Stripe flows.

Browser QA used the visible in-app browser at http://127.0.0.1:8765 with an isolated SQLite database at storage/app/browser-route-qa.sqlite. The normal application database and .env were not changed. UI-created countries and reviews are QA fixtures only.

## Browser checks

- Opened public pages, destinations redirect, login/register forms and health route.
- Submitted the search form for AMM → AQJ, opened flight details, compared two flights, selected outbound and return cabins and observed the saved selection.
- Signed into customer, super-admin and airline accounts using dedicated QA accounts.
- Opened customer pages, saved conversation, review form and legacy booking-review redirect. Submitted a completed flight review and observed the moderation success message.
- Opened all administration pages and country/airport create/edit pages. Created and edited a QA country and observed success messages and updated values.
- Opened every airline panel page. Confirmed an airline account receives the access-denied page at /admin.
- Opened the pending-booking payment page and verified missing-session return redirects. No real payment was submitted. Stripe success/error handling is exercised through automated mocks.
- Private storage GET/PUT endpoints were checked by automated requests: unsigned access returned 403.

## Existing feature gaps observed

A 200 response proves the page rendered; it does not make a preview feature complete.

- Airline dashboard, flights/create/edit/details, bookings/details and reviews remain staff previews.
- Admin airlines, airline admins, customers, cities, flights, bookings/details, payments, moderation, AI monitoring, notifications and messages remain previews. Dashboard, countries, airports and credits are wired to real records.
- My bookings/details/profile, booking lookup, and passenger/seat/review booking steps remain previews. Confirmation explicitly reports no confirmed booking; ticket finalization is not implemented.
- AI displays the intentional provider-unavailable state. Saved conversation rendering and ownership work with test fixtures.
- Real provider integration, production concurrency, ticket issuance and visual inspection at every responsive breakpoint are outside this route smoke test.

## Automated route matrix

GET requests use valid fixture IDs and the expected role. Mutation results below refer to empty/invalid input or redirect-only actions; successful writes are covered by the feature suites. HEAD is registered alongside GET; it was not dispatched separately. The destinations redirect advertises all verbs; GET was exercised.

| Methods | Route | Name | Observed expected result |
| --- | --- | --- | --- |
| GET/HEAD | / | home | 200 |
| GET/HEAD | /about | about | 200 |
| GET/HEAD | /admin | admin.dashboard | 200 |
| GET/HEAD | /admin/ai-conversations | admin.ai-conversations | 200 |
| GET/HEAD | /admin/airline-admins | admin.airline-admins | 200 |
| GET/HEAD | /admin/airlines | admin.airlines | 200 |
| GET/HEAD | /admin/airports | admin.airports | 200 |
| POST | /admin/airports | admin.airports.store | 302 (invalid input / redirect) |
| GET/HEAD | /admin/airports/create | admin.airports.create | 200 |
| PUT | /admin/airports/{airport} | admin.airports.update | 302 (invalid input / redirect) |
| DELETE | /admin/airports/{airport} | admin.airports.destroy | 302 (invalid input / redirect) |
| GET/HEAD | /admin/airports/{airport}/edit | admin.airports.edit | 200 |
| GET/HEAD | /admin/bookings | admin.bookings | 200 |
| GET/HEAD | /admin/bookings/{id} | admin.booking | 200 |
| GET/HEAD | /admin/cities | admin.cities | 200 |
| GET/HEAD | /admin/countries | admin.countries | 200 |
| POST | /admin/countries | admin.countries.store | 302 (invalid input / redirect) |
| GET/HEAD | /admin/countries/create | admin.countries.create | 200 |
| PUT | /admin/countries/{country} | admin.countries.update | 302 (invalid input / redirect) |
| DELETE | /admin/countries/{country} | admin.countries.destroy | 302 (invalid input / redirect) |
| GET/HEAD | /admin/countries/{country}/edit | admin.countries.edit | 200 |
| GET/HEAD | /admin/credits | admin.credits | 200 |
| POST | /admin/credits/{customer} | admin.credits.update | 302 (invalid input / redirect) |
| GET/HEAD | /admin/customers | admin.customers | 200 |
| GET/HEAD | /admin/flights | admin.flights | 200 |
| GET/HEAD | /admin/login | admin.login | 200 |
| POST | /admin/login | admin.login.submit | 302 (invalid input / redirect) |
| GET/HEAD | /admin/messages | admin.messages | 200 |
| GET/HEAD | /admin/notifications | admin.notifications | 200 |
| GET/HEAD | /admin/payments | admin.payments | 200 |
| GET/HEAD | /admin/reviews | admin.reviews | 200 |
| GET/HEAD | /airline | airline.dashboard | 200 |
| GET/HEAD | /airline/bookings | airline.bookings | 200 |
| GET/HEAD | /airline/bookings/{id} | airline.booking | 200 |
| GET/HEAD | /airline/flights | airline.flights | 200 |
| GET/HEAD | /airline/flights/create | airline.create | 200 |
| GET/HEAD | /airline/flights/{id} | airline.flight | 200 |
| GET/HEAD | /airline/flights/{id}/edit | airline.edit | 200 |
| GET/HEAD | /airline/login | airline.login | 200 |
| POST | /airline/login | airline.login.submit | 302 (invalid input / redirect) |
| GET/HEAD | /airline/reviews | airline.reviews | 200 |
| GET/HEAD | /assistant | assistant | 200 |
| GET/HEAD | /book/confirmation | book.confirmation | 200 |
| GET/HEAD | /book/passengers | book.passengers | 200 |
| GET/HEAD | /book/payment | book.payment | 200 |
| POST | /book/payment/intent | book.payment.intent | 302 (invalid input / redirect) |
| GET/HEAD | /book/payment/return | book.payment.return | 302 |
| GET/HEAD | /book/review | book.review | 200 |
| GET/HEAD | /book/seats | book.seats | 200 |
| GET/HEAD | /booking-lookup | booking.lookup | 200 |
| GET/HEAD | /compare | compare | 200 |
| GET/HEAD | /contact | contact | 200 |
| POST | /contact | contact.store | 302 (invalid input / redirect) |
| GET/HEAD | /destinations | destinations | 200 |
| GET/HEAD/POST/PUT/PATCH/DELETE/OPTIONS | /destinations/{country} | — | 302 |
| GET/HEAD | /faq | faq | 200 |
| GET/HEAD | /flights | flights.search | 200 |
| POST | /flights/selection | flights.selection | 302 (invalid input / redirect) |
| GET/HEAD | /flights/{flight} | flights.show | 200 |
| POST | /language/{locale} | home.language | 302 (invalid input / redirect) |
| GET/HEAD | /login | login | 200 |
| POST | /login | login.store | 302 (invalid input / redirect) |
| POST | /logout | logout | 302 (invalid input / redirect) |
| GET/HEAD | /my/assistant | my.assistant | 200 |
| POST | /my/assistant | my.assistant.store | 302 (invalid input / redirect) |
| GET/HEAD | /my/assistant/{conversation} | my.assistant.show | 200 |
| GET/HEAD | /my/booking-flights/{bookingFlight}/review | my.reviews.create | 200 |
| POST | /my/booking-flights/{bookingFlight}/review | my.reviews.store | 302 (invalid input / redirect) |
| GET/HEAD | /my/bookings | my.bookings | 200 |
| GET/HEAD | /my/bookings/{code} | my.booking | 200 |
| GET/HEAD | /my/bookings/{code}/review | my.review | 302 |
| GET/HEAD | /my/credits | my.credits | 200 |
| GET/HEAD | /my/profile | my.profile | 200 |
| GET/HEAD | /privacy | privacy | 200 |
| GET/HEAD | /register | register | 200 |
| POST | /register | register.store | 302 (invalid input / redirect) |
| GET/HEAD | /storage/{path} | storage.local | 403 (unsigned) |
| PUT | /storage/{path} | storage.local.upload | 403 (unsigned) |
| POST | /stripe/webhook | stripe.webhook | 400 (invalid signature) |
| GET/HEAD | /terms | terms | 200 |
| GET/HEAD | /up | — | 200 |

