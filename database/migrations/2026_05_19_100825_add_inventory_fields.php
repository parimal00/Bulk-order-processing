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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(150)->after('base_price');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->unsignedInteger('allocated_quantity')->default(0)->after('quantity');
            $table->unsignedInteger('backorder_quantity')->default(0)->after('allocated_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });

        Schema::table('order_lines', function (Blueprint $table) {
            $table->dropColumn(['allocated_quantity', 'backorder_quantity']);
        });
    }
};
