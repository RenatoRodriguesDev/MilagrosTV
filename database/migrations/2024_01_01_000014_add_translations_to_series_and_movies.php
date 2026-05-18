<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('seasons');
        });
        Schema::table('movies', function (Blueprint $table) {
            $table->json('translations')->nullable()->after('duration');
        });
    }

    public function down(): void
    {
        Schema::table('series', fn($t) => $t->dropColumn('translations'));
        Schema::table('movies', fn($t) => $t->dropColumn('translations'));
    }
};
