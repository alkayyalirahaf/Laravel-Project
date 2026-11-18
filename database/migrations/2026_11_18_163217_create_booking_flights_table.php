<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_flights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings');
            $table->foreignId('flight_id')->constrained('flights');
            $table->foreignId('flight_class_id')->constrained('flight_classes');
            $table->enum('direction', ['outbound', 'return']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_flights');
    }
};
