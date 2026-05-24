<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('failed_bulk_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_upload_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('sku')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('error_code');
            $table->text('error_message');
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_bulk_rows');
    }
};
