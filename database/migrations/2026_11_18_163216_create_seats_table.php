<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('flight_class_id')->constrained('flight_classes');
            $table->string('seat_number', 5);
            $table->enum('status', ['available', 'held', 'booked']);
            $table->foreignId('held_by')->nullable()->constrained('customers')->nullOnDelete();
            $table->dateTime('held_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
