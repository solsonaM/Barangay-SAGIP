<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feature 6: Response Assignment Classification — records which
     * responder the ML/scoring service recommended (or an official
     * manually assigned) for a given request, and the score that produced
     * the recommendation, for auditability.
     */
    public function up(): void
    {
        Schema::create('response_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('response_personnel_id')->constrained('response_personnel')->cascadeOnDelete();
            $table->decimal('assignment_score', 6, 4)->nullable();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->boolean('specialization_match')->default(false);
            $table->boolean('was_manual_override')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_assignments');
    }
};
