<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->uuid('operation_id')->nullable()->unique();
            $table->foreignId('actor_user_id')->nullable()->constrained('users');
            $table->string('operation_action', 10)->nullable();
            $table->decimal('requested_amount', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropForeign(['actor_user_id']);
            $table->dropUnique(['operation_id']);
            $table->dropColumn(['operation_id', 'actor_user_id', 'operation_action', 'requested_amount']);
        });
    }
};
