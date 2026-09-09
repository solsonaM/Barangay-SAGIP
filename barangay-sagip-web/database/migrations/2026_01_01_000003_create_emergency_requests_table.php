<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Feature 2: Emergency/Assistance Request Submission
     * Feature 3: ML-Based Request Classification    (category, category_confidence)
     * Feature 4: Urgency/Priority Classification     (urgency, urgency_confidence)
     * Feature 5: Request Validation                  (needs_review, validated_at, validated_by)
     * Feature 7: Real-Time Urgent Status Tracking     (status)
     * Feature 8: Location Map Generator               (latitude, longitude)
     */
    public function up(): void
    {
        Schema::create('emergency_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained('users')->cascadeOnDelete();
            $table->text('description');

            // Feature 3 & 4 outputs (nullable until the ML service responds)
            $table->string('category')->nullable();
            $table->decimal('category_confidence', 5, 4)->nullable();
            $table->string('urgency')->nullable();
            $table->decimal('urgency_confidence', 5, 4)->nullable();

            // Feature 5
            $table->boolean('needs_review')->default(false);
            $table->string('review_reason')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            // Feature 8
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Feature 7
            $table->string('status')->default('submitted');
            // submitted -> needs_review -> validated -> assigned -> en_route -> resolved (or cancelled)

            $table->timestamps();

            $table->index(['status', 'urgency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_requests');
    }
};
