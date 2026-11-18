<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 12)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->enum('trip_type', ['one_way', 'round_trip']);
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed']);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('taxes', 10, 2);
            $table->decimal('fees', 10, 2);
            $table->decimal('total', 10, 2);
            $table->decimal('credits_used', 10, 2);
            $table->decimal('amount_paid', 10, 2);
            $table->string('contact_email', 255);
            $table->string('contact_phone', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
