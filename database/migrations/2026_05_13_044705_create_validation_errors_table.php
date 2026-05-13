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
        Schema::create('validation_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_upload_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number')->index();
            $table->string('column_name', 128)->nullable();
            $table->string('error_code', 64)->index();
            $table->text('error_message');
            $table->text('raw_value')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validation_errors');
    }
};
