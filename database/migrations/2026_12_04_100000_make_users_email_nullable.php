<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Telegram orqali kirgan foydalanuvchining emaili bo'lmasligi mumkin. UNIQUE indeks saqlanadi (bir nechta NULL ruxsat).
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // Avval Telegram uchun yaratilgan soxta manzillar (tg123.abc@telegram.invalid) endi kerak emas.
        DB::table('users')->where('email', 'like', '%@telegram.invalid')->update(['email' => null]);
    }

    public function down(): void
    {
        // NOT NULL ga qaytishdan oldin bo'sh emaillar to'ldiriladi.
        foreach (DB::table('users')->whereNull('email')->pluck('id') as $id) {
            DB::table('users')->where('id', $id)->update(['email' => "user{$id}@placeholder.invalid"]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
