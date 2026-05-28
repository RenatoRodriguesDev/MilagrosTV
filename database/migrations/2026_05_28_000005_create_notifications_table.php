<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // new_episode, new_movie
            $table->string('title');
            $table->string('message');
            $table->string('url')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamps();
        });

        Schema::table('movies', function (Blueprint $table) {
            $table->string('trailer_url')->nullable()->after('poster_url');
        });

        Schema::table('series', function (Blueprint $table) {
            $table->string('trailer_url')->nullable()->after('poster_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::table('movies', fn($t) => $t->dropColumn('trailer_url'));
        Schema::table('series', fn($t) => $t->dropColumn('trailer_url'));
    }
};
