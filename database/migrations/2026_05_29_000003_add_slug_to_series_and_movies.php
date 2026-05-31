<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        // Generate slugs for existing records
        foreach (\App\Models\Serie::all() as $serie) {
            $serie->slug = self::makeSlug($serie->title, 'series', $serie->id);
            $serie->saveQuietly();
        }

        foreach (\App\Models\Movie::all() as $movie) {
            $movie->slug = self::makeSlug($movie->title, 'movies', $movie->id);
            $movie->saveQuietly();
        }

        Schema::table('series', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('series', fn($t) => $t->dropColumn('slug'));
        Schema::table('movies', fn($t) => $t->dropColumn('slug'));
    }

    private static function makeSlug(string $title, string $table, int $excludeId): string
    {
        $base  = Str::slug($title) ?: 'item';
        $slug  = $base;
        $count = 1;
        while (\Illuminate\Support\Facades\DB::table($table)->where('slug', $slug)->where('id', '!=', $excludeId)->exists()) {
            $slug = "{$base}-" . ++$count;
        }
        return $slug;
    }
};
