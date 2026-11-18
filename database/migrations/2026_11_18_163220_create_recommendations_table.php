<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('flight_id')->constrained('flights');
            $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations');
            $table->string('reason', 255);
            $table->enum('source', ['chatbot', 'search', 'profile']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
