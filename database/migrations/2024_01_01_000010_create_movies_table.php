<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->integer('year')->nullable();
            $table->json('genres')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('tmdb_id')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
