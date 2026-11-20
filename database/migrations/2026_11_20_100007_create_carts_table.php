<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // mehmon savati uchun user_id bo'sh bo'lishi mumkin
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
