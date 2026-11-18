<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_flight_id')->constrained('booking_flights');
            $table->foreignId('passenger_id')->constrained('passengers');
            $table->foreignId('seat_id')->nullable()->constrained('seats');
            $table->string('ticket_number', 20)->unique();
            $table->string('qr_code', 255);
            $table->string('pdf_path', 255)->nullable();
            $table->enum('status', ['issued', 'cancelled', 'used']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
