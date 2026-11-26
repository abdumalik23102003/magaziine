<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'parent_id'], 'comments_thread_index');
        });

        // Hisoblagichlar jadvalda saqlanadi: kartalarda har safar sanash shart emas.
        foreach (['products', 'posts'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->unsignedInteger('rating_count')->default(0);
                $table->decimal('rating_avg', 3, 2)->default(0);
                $table->unsignedInteger('comments_count')->default(0);

                if ($name === 'products') {
                    $table->unsignedInteger('views')->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rating_count', 'rating_avg', 'comments_count', 'views']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['rating_count', 'rating_avg', 'comments_count']);
        });

        Schema::dropIfExists('comments');
    }
};
