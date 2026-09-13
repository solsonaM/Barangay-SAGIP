<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the raw response from the tokenization service for every
     * classification call, so officials/researchers can audit classifier
     * behavior and the group can compute real accuracy later against
     * outcomes recorded through Feature 12 (Report Generator).
     */
    public function up(): void
    {
        Schema::create('tokenization_classification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_request_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('was_successful')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokenization_classification_logs');
    }
};
