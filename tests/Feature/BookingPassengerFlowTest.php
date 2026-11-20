<?php

namespace Tests\Feature;

use App\Mail\BookingTickets;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Ticket;
use App\Services\PaymentService;
use App\Models\Country;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingPassengerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_validate_and_save_passenger_details_before_opening_seats(): void
    {
        $user = User::factory()->create();
        $user->customer()->create(['credit_balance' => 0]);
        $country = Country::create(['name' => 'Jordan', 'code' => 'JO', 'currency' => 'JOD', 'language' => 'Arabic']);
        $city = $country->cities()->create(['name' => 'Amman']);
        $origin = Airport::create(['city_id' => $city->id, 'code' => 'AMM', 'name' => 'Amman']);
        $destination = Airport::create(['city_id' => $city->id, 'code' => 'AQJ', 'name' => 'Aqaba']);
        $airline = Airline::create(['name' => 'Test Airline', 'code' => 'TST', 'country' => 'Jordan', 'status' => 'active']);
        $flight = Flight::create([
            'airline_id' => $airline->id, 'flight_number' => 'TST101',
            'origin_airport_id' => $origin->id, 'destination_airport_id' => $destination->id,
            'departure_at' => now()->addDays(5), 'arrival_at' => now()->addDays(5)->addHour(),
            'stops' => 0, 'status' => 'scheduled',
        ]);
        $cabin = $flight->flightClasses()->create([
            'class' => 'economy', 'price' => 100, 'seat_rows' => 1, 'seats_per_row' => 2,
            'total_seats' => 2, 'cabin_baggage_kg' => 7, 'checked_baggage_kg' => 20,
        ]);
        foreach (['1A', '1B'] as $number) {
            $cabin->seats()->create(['seat_number' => $number, 'status' => 'available']);
        }

        $selection = [
            'trip' => 'one_way', 'passengers' => 2,
            'passenger_counts' => ['adults' => 1, 'youth' => 1, 'children' => 0, 'infants' => 0],
            'outbound' => ['flight_id' => $flight->id, 'flight_class_id' => $cabin->id, 'price' => '100.00'],
        ];

        $this->get(route('book.passengers'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('book.passengers'))->assertRedirect(route('flights.search'));
        $page = $this->withSession(['dev3.search_selection' => $selection])->get(route('book.passengers'));
        $page->assertOk()->assertSee('TST101')->assertSee('Youth')->assertSee('passengers[1][first_name]');
        $selectionId = $page->viewData('selectionId');
        $this->post(route('book.passengers.store'), ['selection_id' => $selectionId])
            ->assertRedirect(route('book.passengers'))->assertSessionHasErrors('passengers');

        $adult = [
            'first_name' => 'Test', 'last_name' => 'Traveler', 'date_of_birth' => '1990-01-01',
            'gender' => 'female', 'nationality' => 'Jordanian', 'passport_number' => 'DEMO123',
            'passport_country' => 'Jordan', 'passport_expiry' => now()->addYear()->toDateString(),
        ];
        $youth = array_replace($adult, ['first_name' => 'Young', 'date_of_birth' => now()->subYears(14)->toDateString()]);
        $this->post(route('book.passengers.store'), [
            'selection_id' => $selectionId, 'passengers' => [$adult, $youth], 'contact_email' => $user->email,
        ])->assertSessionHasNoErrors()->assertRedirect(route('book.seats'));

        $draft = Crypt::decrypt(session('dev4.passenger_draft.details'));
        $this->assertSame(['adult', 'youth'], array_column($draft['passengers'], 'type'));
        $this->get(route('book.passengers'))->assertOk()->assertSee('value="Young"', false);
        $page = $this->get(route('book.seats'))->assertOk()->assertSee('1A');
        $code = $page->viewData('code');
        $ids = $cabin->seats()->pluck('id')->all();
        $this->from(route('book.seats'))->post(route('book.seats.store'), [
            'booking_code' => $code, 'seats' => ['outbound' => [$ids[0], $ids[0]]],
        ])->assertRedirect(route('book.seats'))->assertSessionHasErrors('seats');
        $this->assertDatabaseCount('bookings', 0);

        $payload = ['booking_code' => $code, 'seats' => ['outbound' => $ids]];
        $this->post(route('book.seats.store'), $payload)->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->post(route('book.seats.store'), $payload)->assertRedirect(route('book.review', ['booking_id' => $booking->id]));
        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame('pending', $booking->status);
        $this->assertSame('200.00', $booking->total);
        $this->assertSame(2, $cabin->seats()->where('status', 'held')->count());
        $this->assertDatabaseCount('tickets', 0);
        $this->get(route('book.review', ['booking_id' => $booking->id]))->assertOk()->assertSee('Continue to payment');

        config(['services.stripe.secret' => null]);
        $this->post(route('book.payment.intent'), ['booking_id' => $booking->id])
            ->assertRedirect(route('book.payment', ['booking_id' => $booking->id]))
            ->assertSessionHas('status', 'Stripe Checkout is currently unavailable. Your booking is still pending.');
        $this->assertDatabaseCount('payments', 0);

        Mail::fake();
        $payments = app(PaymentService::class);
        $this->assertTrue($payments->markPaid('pi_dev4_test', $booking->id, $booking->customer_id, '200.00'));
        $this->assertFalse($payments->markPaid('pi_dev4_test', $booking->id, $booking->customer_id, '200.00'));
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('200.00', $booking->fresh()->amount_paid);
        $this->assertSame(2, Ticket::where('status', 'issued')->count());
        $this->assertSame(2, $cabin->seats()->where('status', 'booked')->count());
        $this->assertDatabaseCount('notification_logs', 1);
        Mail::assertSent(BookingTickets::class, 1);
        Mail::assertSent(BookingTickets::class, function (BookingTickets $mail) use ($user): bool {
            $html = $mail->render();
            foreach (['Test Traveler', 'Young Traveler', 'TST101', 'Seat 1A', 'Seat 1B'] as $detail) {
                $this->assertStringContainsString($detail, $html);
            }

            $this->assertStringNotContainsString('DEMO123', $html);

            return $mail->hasTo($user->email);
        });

        $this->get(route('book.confirmation', ['booking_id' => $booking->id]))->assertOk()->assertSee('Confirmed');
        $this->get(route('my.bookings'))->assertOk()->assertSee($booking->code);
        $this->post(route('booking.lookup.search'), ['code' => $booking->code])->assertRedirect(route('my.booking', $booking->code));
        $this->patch(route('my.profile.update'), ['name' => 'Updated Traveler', 'phone' => '0791234567'])->assertRedirect(route('my.profile'));
        $this->assertSame('Updated Traveler', $user->fresh()->name);

        foreach (['2A', '2B'] as $number) {
            $cabin->seats()->create(['seat_number' => $number, 'status' => 'available']);
        }

        $this->post(route('book.passengers.store'), [
            'selection_id' => $selectionId, 'passengers' => [$adult, $youth], 'contact_email' => $user->email,
        ])->assertRedirect(route('book.seats'));
        $ids = $cabin->seats()->where('status', 'available')->pluck('id')->all();
        $this->post(route('book.seats.store'), [
            'booking_code' => session('dev4.passenger_draft.booking_code'), 'seats' => ['outbound' => $ids],
        ])->assertSessionHasNoErrors();
        $cancelled = Booking::latest('id')->firstOrFail();
        $this->post(route('my.booking.cancel', $cancelled->code))->assertRedirect(route('my.booking', $cancelled->code));
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertSame(2, $cabin->seats()->where('status', 'available')->count());
        $this->assertTrue($payments->markPaid('pi_dev4_late', $cancelled->id, $cancelled->customer_id, '200.00'));
        $this->assertSame('cancelled', $cancelled->fresh()->status);
        $this->assertDatabaseCount('tickets', 2);

        $this->post(route('book.passengers.store'), [
            'selection_id' => $selectionId, 'passengers' => [$adult, $youth], 'contact_email' => $user->email,
        ])->assertRedirect(route('book.seats'));
        $this->post(route('book.seats.store'), [
            'booking_code' => session('dev4.passenger_draft.booking_code'), 'seats' => ['outbound' => $ids],
        ])->assertSessionHasNoErrors();
        $expired = Booking::latest('id')->firstOrFail();
        $this->travel(16)->minutes();
        $this->get(route('book.review', ['booking_id' => $expired->id]))->assertOk()->assertSee('Your seat hold has expired');
        $this->post(route('book.payment.intent'), ['booking_id' => $expired->id])->assertConflict();
        $this->assertSame(2, $cabin->seats()->availableForBooking()->count());
        $this->assertTrue($payments->markPaid('pi_dev4_expired', $expired->id, $expired->customer_id, '200.00'));
        $this->assertSame('pending', $expired->fresh()->status);
        $this->assertDatabaseCount('tickets', 2);
        $this->travelBack();

        $admin = User::factory()->create();
        $admin->superAdmin()->create([]);
        $this->actingAs($admin)->get(route('admin.bookings'))->assertOk()->assertSee($booking->code);
        $this->get(route('admin.booking', $booking->id))->assertOk()->assertSee('TST101');

        $other = User::factory()->create();
        $other->customer()->create(['credit_balance' => 0]);
        $ticket = Ticket::firstOrFail();
        $this->actingAs($user)->get(route('my.ticket', $ticket->ticket_number))
            ->assertOk()->assertSee('Print / Save as PDF')->assertSee($ticket->ticket_number);
        $this->actingAs($other)->get(route('my.ticket', $ticket->ticket_number))->assertNotFound();
        $this->get(route('my.booking', $booking->code))->assertNotFound();

        $this->travel(16)->minutes();
        $returnFlight = Flight::create([
            'airline_id' => $airline->id, 'flight_number' => 'TST102',
            'origin_airport_id' => $destination->id, 'destination_airport_id' => $origin->id,
            'departure_at' => now()->addDays(9), 'arrival_at' => now()->addDays(9)->addHour(),
            'stops' => 0, 'status' => 'scheduled',
        ]);
        $returnCabin = $returnFlight->flightClasses()->create([
            'class' => 'economy', 'price' => 120, 'seat_rows' => 1, 'seats_per_row' => 2,
            'total_seats' => 2, 'cabin_baggage_kg' => 7, 'checked_baggage_kg' => 20,
        ]);
        $returnSeats = [];
        foreach (['3A', '3B'] as $number) {
            $returnSeats[] = $returnCabin->seats()->create(['seat_number' => $number, 'status' => 'available'])->id;
        }

        $selection['trip'] = 'round_trip';
        $selection['return'] = ['flight_id' => $returnFlight->id, 'flight_class_id' => $returnCabin->id, 'price' => '120.00'];
        $page = $this->actingAs($user)->withSession(['dev3.search_selection' => $selection])->get(route('book.passengers'));
        $page->assertOk()->assertSee('TST102');
        $this->post(route('book.passengers.store'), [
            'selection_id' => $page->viewData('selectionId'), 'passengers' => [$adult, $youth], 'contact_email' => $user->email,
        ])->assertRedirect(route('book.seats'));
        $this->post(route('book.seats.store'), [
            'booking_code' => session('dev4.passenger_draft.booking_code'),
            'seats' => ['outbound' => $ids, 'return' => $returnSeats],
        ])->assertSessionHasNoErrors();
        $roundTrip = Booking::latest('id')->firstOrFail();
        $this->assertSame('440.00', $roundTrip->total);
        Mail::fake();
        $this->assertTrue($payments->markPaid('pi_dev4_round_trip', $roundTrip->id, $roundTrip->customer_id, '440.00'));
        $this->assertSame('confirmed', $roundTrip->fresh()->status);
        foreach ($roundTrip->passengers()->withCount('tickets')->get() as $passenger) {
            $this->assertSame(2, $passenger->tickets_count);
        }
        Mail::assertSent(BookingTickets::class, function (BookingTickets $mail): bool {
            $html = $mail->render();
            $this->assertSame(2, substr_count($html, 'Outbound · TST101'));
            $this->assertSame(2, substr_count($html, 'Return · TST102'));

            return true;
        });
        $this->travelBack();
    }
}
