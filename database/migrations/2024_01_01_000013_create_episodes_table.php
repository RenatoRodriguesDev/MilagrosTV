<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('serie_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('season');
            $table->unsignedSmallInteger('episode');
            $table->string('title')->nullable();
            $table->string('video_path')->nullable();
            $table->timestamps();
            $table->unique(['serie_id', 'season', 'episode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
