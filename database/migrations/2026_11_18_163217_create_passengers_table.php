<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passengers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings');
            $table->enum('type', ['adult', 'child', 'infant']);
            $table->string('title', 10)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality', 60);
            $table->string('passport_number', 20);
            $table->string('passport_country', 60);
            $table->date('passport_expiry');
            $table->string('email', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('special_assistance', 255)->nullable();
            $table->unsignedTinyInteger('filled_by_ai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passengers');
    }
};
