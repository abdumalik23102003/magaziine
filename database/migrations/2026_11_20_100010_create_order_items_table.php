<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            // variant o'chib ketsa ham buyurtma tarixi saqlansin
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            // buyurtma paytidagi nusxa (snapshot)
            $table->string('product_name');
            $table->string('variant_name');
            $table->decimal('price', 12, 2);
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
