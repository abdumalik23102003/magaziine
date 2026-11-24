<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('user_id');
            $table->decimal('subtotal', 12, 2)->default(0)->after('status');
            $table->decimal('tax', 12, 2)->default(0)->after('subtotal');
            $table->decimal('delivery_fee', 12, 2)->default(0)->after('tax');
            $table->string('delivery_method', 32)->nullable()->after('total_price');
            $table->string('payment_method', 32)->nullable()->after('delivery_method');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'subtotal', 'tax', 'delivery_fee', 'delivery_method', 'payment_method']);
        });
    }
};
