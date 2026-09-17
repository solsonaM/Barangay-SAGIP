<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operational indexes and data-integrity constraints for common
     * authorization, assignment, tracking, and reporting queries.
     */
    public function up(): void
    {
        Schema::table('response_personnel', function (Blueprint $table) {
            $table->unique('user_id', 'response_personnel_user_id_unique');
        });

        Schema::table('emergency_requests', function (Blueprint $table) {
            $table->index('resident_id', 'emergency_requests_resident_id_index');
            $table->index(['status', 'created_at'], 'emergency_requests_status_created_at_index');
        });

        Schema::table('request_status_logs', function (Blueprint $table) {
            $table->index(['emergency_request_id', 'created_at'], 'request_status_logs_request_created_at_index');
        });

        Schema::table('response_assignments', function (Blueprint $table) {
            $table->index(
                ['emergency_request_id', 'completed_at'],
                'response_assignments_request_completed_index'
            );
            $table->index(
                ['response_personnel_id', 'completed_at'],
                'response_assignments_personnel_completed_index'
            );
        });

        Schema::table('tokenization_classification_logs', function (Blueprint $table) {
            $table->index(
                ['emergency_request_id', 'created_at'],
                'tokenization_logs_request_created_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tokenization_classification_logs', function (Blueprint $table) {
            $table->dropIndex('tokenization_logs_request_created_at_index');
        });

        Schema::table('response_assignments', function (Blueprint $table) {
            $table->dropIndex('response_assignments_request_completed_index');
            $table->dropIndex('response_assignments_personnel_completed_index');
        });

        Schema::table('request_status_logs', function (Blueprint $table) {
            $table->dropIndex('request_status_logs_request_created_at_index');
        });

        Schema::table('emergency_requests', function (Blueprint $table) {
            $table->dropIndex('emergency_requests_resident_id_index');
            $table->dropIndex('emergency_requests_status_created_at_index');
        });

        Schema::table('response_personnel', function (Blueprint $table) {
            $table->dropUnique('response_personnel_user_id_unique');
        });
    }
};
