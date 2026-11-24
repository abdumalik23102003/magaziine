<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $slugAdded = ! Schema::hasColumn('brands', 'slug');

        Schema::table('brands', function (Blueprint $table) use ($slugAdded) {
            if ($slugAdded) {
                $table->string('slug')->nullable()->after('name');
            }

            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('tagline', 160)->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
        });

        $used = DB::table('brands')->whereNotNull('slug')->where('slug', '!=', '')->pluck('slug')->all();

        DB::table('brands')
            ->where(fn ($query) => $query->whereNull('slug')->orWhere('slug', ''))
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function ($brand) use (&$used): void {
                $base = Str::slug($brand->name) ?: 'brand';
                $slug = $base;
                $n = 2;

                while (in_array($slug, $used, true)) {
                    $slug = $base.'-'.$n++;
                }

                $used[] = $slug;
                DB::table('brands')->where('id', $brand->id)->update(['slug' => $slug]);
            });

        if ($slugAdded) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('slug')->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path', 'cover_path', 'tagline', 'description',
                'website', 'is_featured', 'is_active', 'sort_order',
            ]);
        });
    }
};
