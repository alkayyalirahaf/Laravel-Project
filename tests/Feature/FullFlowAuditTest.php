<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\City;
use App\Models\Country;
use App\Models\Flight;
use App\Models\FlightClass;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FullFlowAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_500_page_hides_the_exception_detail(): void
    {
        config(['app.debug' => false]);
        Route::get('/__audit/server-error', fn () => throw new \RuntimeException('audit-only secret'));

        $this->get('/__audit/server-error')
            ->assertStatus(500)
            ->assertSee('Something went wrong')
            ->assertDontSee('audit-only secret');
    }

    public function test_passport_scan_endpoint_is_registered_and_guest_protected(): void
    {
        $this->post('/book/passport-scan')->assertRedirect(route('login'));
    }

    public function test_two_customers_cannot_hold_the_same_seat(): void
    {
        [$flight, $class, $seat, $selection] = $this->oneSeatItinerary();
        $first = $this->customer('first.audit@example.test');
        $second = $this->customer('second.audit@example.test');
        $firstDraft = $this->preparePassengerDraft($first, $selection, 'First');
        $secondDraft = $this->preparePassengerDraft($second, $selection, 'Second');

        $this->actingAs($first)->withSession([
            'dev3.search_selection' => $selection,
            'dev4.passenger_draft' => $firstDraft,
        ])->post(route('book.seats.store'), [
            'booking_code' => $firstDraft['booking_code'],
            'seats' => ['outbound' => [$seat->id]],
        ])->assertRedirect();

        $this->actingAs($second)->withSession([
            'dev3.search_selection' => $selection,
            'dev4.passenger_draft' => $secondDraft,
        ])->from(route('book.seats'))->post(route('book.seats.store'), [
            'booking_code' => $secondDraft['booking_code'],
            'seats' => ['outbound' => [$seat->id]],
        ])->assertRedirect(route('book.seats'))->assertSessionHasErrors('seats');

        $this->assertSame('held', $seat->fresh()->status);
        $this->assertSame(1, Booking::count());
    }

    public function test_new_seat_holds_expire_after_ten_minutes(): void
    {
        [$flight, $class, $seat, $selection] = $this->oneSeatItinerary();
        $user = $this->customer('hold.audit@example.test');
        $draft = $this->preparePassengerDraft($user, $selection, 'Hold');
        $startedAt = now()->startOfSecond();

        $this->actingAs($user)->withSession([
            'dev3.search_selection' => $selection,
            'dev4.passenger_draft' => $draft,
        ])->post(route('book.seats.store'), [
            'booking_code' => $draft['booking_code'],
            'seats' => ['outbound' => [$seat->id]],
        ])->assertRedirect();

        $heldUntil = $seat->fresh()->held_until->timestamp;
        $seconds = $heldUntil - $startedAt->timestamp;
        $this->assertGreaterThanOrEqual(600, $seconds);
        $this->assertLessThanOrEqual(601, $seconds, "Observed seat hold duration: {$seconds} seconds.");
    }

    public function test_paid_customer_booking_can_be_cancelled_and_refunded_by_policy(): void
    {
        $user = $this->customer('cancel.audit@example.test');
        $booking = $user->customer->bookings()->create([
            'code' => 'YTAUDITCANCEL', 'trip_type' => 'one_way', 'status' => 'confirmed',
            'subtotal' => '100.00', 'taxes' => '0.00', 'fees' => '0.00', 'total' => '100.00',
            'credits_used' => '0.00', 'amount_paid' => '100.00', 'contact_email' => $user->email,
        ]);
        Payment::create([
            'booking_id' => $booking->id,
            'stripe_payment_intent_id' => 'pi_audit_cancel',
            'amount' => '100.00',
            'status' => 'succeeded',
        ]);

        $this->actingAs($user)->post(route('my.booking.cancel', $booking->code))
            ->assertRedirect(route('my.booking', $booking->code));
    }

    private function oneSeatItinerary(): array
    {
        $country = Country::create(['name' => 'Auditland', 'code' => 'QA', 'currency' => 'QAD', 'language' => 'English']);
        $originCity = City::create(['country_id' => $country->id, 'name' => 'Audit Origin']);
        $destinationCity = City::create(['country_id' => $country->id, 'name' => 'Audit Destination']);
        $origin = Airport::create(['city_id' => $originCity->id, 'code' => 'AUA', 'name' => 'Audit Origin Airport']);
        $destination = Airport::create(['city_id' => $destinationCity->id, 'code' => 'AUB', 'name' => 'Audit Destination Airport']);
        $airline = Airline::create(['name' => 'Audit Air', 'code' => 'AUD', 'country' => 'Auditland', 'status' => 'active']);
        $departure = now()->addDays(5)->startOfHour();
        $flight = Flight::create([
            'airline_id' => $airline->id,
            'flight_number' => 'AUD100',
            'origin_airport_id' => $origin->id,
            'destination_airport_id' => $destination->id,
            'departure_at' => $departure,
            'arrival_at' => $departure->copy()->addHour(),
            'stops' => 0,
            'status' => 'scheduled',
        ]);
        $class = FlightClass::create([
            'flight_id' => $flight->id, 'class' => 'economy', 'price' => '100.00',
            'seat_rows' => 1, 'seats_per_row' => 1, 'total_seats' => 1,
            'cabin_baggage_kg' => 7, 'checked_baggage_kg' => 20,
        ]);
        $seat = Seat::create(['flight_class_id' => $class->id, 'seat_number' => '1A', 'status' => 'available']);
        $selection = [
            'trip' => 'one_way',
            'passengers' => 1,
            'passenger_counts' => ['adults' => 1, 'youth' => 0, 'children' => 0, 'infants' => 0],
            'outbound' => ['flight_id' => $flight->id, 'flight_class_id' => $class->id, 'price' => '100.00'],
        ];

        return [$flight, $class, $seat, $selection];
    }

    private function customer(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->customer()->create(['credit_balance' => '0.00']);

        return $user;
    }

    private function preparePassengerDraft(User $user, array $selection, string $name): array
    {
        $selectionId = hash('sha256', serialize($selection));
        $this->actingAs($user)->withSession(['dev3.search_selection' => $selection])
            ->get(route('book.passengers'))->assertOk();
        $this->post(route('book.passengers.store'), [
            'selection_id' => $selectionId,
            'passengers' => [[
                'first_name' => $name,
                'last_name' => 'Audit',
                'date_of_birth' => '1990-01-01',
                'gender' => 'female',
                'nationality' => 'Auditland',
                'passport_number' => 'AUDIT123',
                'passport_country' => 'Auditland',
                'passport_expiry' => now()->addYears(5)->toDateString(),
            ]],
            'contact_email' => $user->email,
        ])->assertRedirect(route('book.seats'));

        return session('dev4.passenger_draft');
    }
}
