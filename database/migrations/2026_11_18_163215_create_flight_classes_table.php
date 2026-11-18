<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_classes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('flight_id')->constrained('flights');
            $table->enum('class', ['economy', 'business', 'first']);
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('seat_rows');
            $table->unsignedTinyInteger('seats_per_row');
            $table->unsignedSmallInteger('total_seats');
            $table->unsignedTinyInteger('cabin_baggage_kg');
            $table->unsignedTinyInteger('checked_baggage_kg');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_classes');
    }
};
