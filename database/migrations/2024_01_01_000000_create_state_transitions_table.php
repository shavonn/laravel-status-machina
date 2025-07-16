<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create(config('status-machina.db_history_tracking.history_table_name', 'state_transitions'), function (Blueprint $table) {
            $table->id();

            // Model information
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('property', 50)->default('status');

            // Transition details
            $table->string('transition')->nullable();
            $table->string('from_state');
            $table->string('to_state');

            // Context data (array stored as JSON)
            $table->json('context')->nullable();

            // User who made the transition (optional)
            $table->nullableMorphs('transitioner');

            // Metadata
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['model_type', 'model_id']);
            $table->index(['model_type', 'model_id', 'property']);
            $table->index('transition');
            $table->index('from_state');
            $table->index('to_state');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('status-machina.db_history_tracking.history_table_name', 'state_transitions'));
    }
};
