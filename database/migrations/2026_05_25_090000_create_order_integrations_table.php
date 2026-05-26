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
        Schema::create('order_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 64)->default('erp_stub');
            $table->string('status', 32)->default('pending')->index();
            $table->string('internal_status', 32)->nullable();
            $table->string('external_status', 64)->nullable();
            $table->string('external_reference', 128)->nullable()->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('last_error')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('last_callback_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'provider']);
            $table->index(['provider', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_integrations');
    }
};
