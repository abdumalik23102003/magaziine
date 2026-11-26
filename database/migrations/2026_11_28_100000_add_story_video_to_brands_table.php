<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('story_video_path')->nullable();
            $table->boolean('story_enabled')->default(false);
            $table->string('story_title', 120)->nullable();
            $table->string('story_subtitle', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['story_video_path', 'story_enabled', 'story_title', 'story_subtitle']);
        });
    }
};
