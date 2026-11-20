<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Flight;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_is_complete_consistent_and_repeatable_without_overwriting_edits(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seed(DemoSeeder::class);

        $tables = ['countries', 'cities', 'airports', 'airlines', 'users', 'super_admins', 'airline_admins', 'customers', 'flights', 'flight_classes', 'seats', 'bookings', 'booking_flights', 'passengers', 'tickets', 'payments', 'credit_transactions', 'reviews', 'notification_logs', 'contact_messages', 'ai_conversations', 'recommendations'];
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
            $this->assertGreaterThan(0, $counts[$table], $table);
        }
        $this->assertSame(462, Flight::count());
        $this->assertSame(16, Booking::count());
        $user = User::where('email', 'traveler@demo.example.test')->firstOrFail();
        $this->assertTrue(Hash::check(DemoSeeder::PASSWORD, $user->password));
        $user->update(['name' => 'Edited traveler']);
        $this->seed(DemoSeeder::class);
        foreach ($tables as $table) {
            $this->assertSame($counts[$table], DB::table($table)->count(), $table);
        }
        $this->assertSame('Edited traveler', $user->fresh()->name);
        foreach (Ticket::with(['seat', 'bookingFlight', 'passenger'])->get() as $ticket) {
            $this->assertSame('booked', $ticket->seat->status);
            $this->assertSame($ticket->bookingFlight->flight_class_id, $ticket->seat->flight_class_id);
            $this->assertSame($ticket->bookingFlight->booking_id, $ticket->passenger->booking_id);
        }
        $this->assertSame(Ticket::count(), Ticket::distinct()->count('seat_id'));
        $this->assertGreaterThan(0, Seat::where('status', 'held')->count());
        foreach (Booking::whereIn('status', ['confirmed', 'completed'])->get() as $booking) {
            $this->assertSame($booking->total, $booking->amount_paid);
        }
        Mail::assertNothingSent();
        $this->actingAs($user)->get('/my/bookings')->assertOk()->assertSee('DEMO');
        $this->get('/my/credits')->assertOk();
        $this->get('/assistant')->assertOk()->assertSee('planning a Dubai trip');
        $this->get('/flights?from=AMM&to=DXB&depart='.today()->addDays(3)->toDateString().'&trip=one_way')->assertOk()->assertSee('Royal Jordanian')->assertSee('Emirates');
        $admin = User::where('email', 'admin@demo.example.test')->firstOrFail();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->get('/admin/bookings')->assertOk();
    }

    public function test_demo_refuses_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\RuntimeException::class);
        app(DemoSeeder::class)->run();
    }
}
