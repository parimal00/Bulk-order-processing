<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('bulk_upload_id');
        });

        Schema::table('failed_bulk_rows', function (Blueprint $table) {
            $table->index(['bulk_upload_id', 'row_number']);
        });

        Schema::table('validation_errors', function (Blueprint $table) {
            $table->index(['bulk_upload_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('validation_errors', function (Blueprint $table) {
            $table->dropIndex(['bulk_upload_id', 'row_number']);
        });

        Schema::table('failed_bulk_rows', function (Blueprint $table) {
            $table->dropIndex(['bulk_upload_id', 'row_number']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['bulk_upload_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
