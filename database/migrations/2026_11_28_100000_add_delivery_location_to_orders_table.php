<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * orders.address (to'liq matn) va phone/customer_name (qabul qiluvchi) o'z joyida qoladi.
     * Bu yerda faqat tuzilmali qismlar va koordinatalar qo'shiladi; hammasi nullable (eski buyurtmalar buzilmaydi).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();
            $table->string('delivery_country', 80)->nullable();
            $table->string('delivery_country_code', 2)->nullable();
            $table->string('delivery_region', 80)->nullable();
            $table->string('delivery_city', 50)->nullable();
            $table->string('delivery_district', 80)->nullable();
            $table->string('delivery_street', 150)->nullable();
            $table->string('delivery_house_number', 20)->nullable();
            $table->string('delivery_postcode', 20)->nullable();
            $table->string('delivery_apartment', 50)->nullable();
            $table->string('delivery_entrance', 20)->nullable();
            $table->string('delivery_floor', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_lat', 'delivery_lng', 'delivery_country', 'delivery_country_code', 'delivery_region',
                'delivery_city', 'delivery_district', 'delivery_street', 'delivery_house_number', 'delivery_postcode',
                'delivery_apartment', 'delivery_entrance', 'delivery_floor',
            ]);
        });
    }
};
