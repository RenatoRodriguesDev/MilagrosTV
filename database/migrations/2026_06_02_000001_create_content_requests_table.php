<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tmdb_id');
            $table->enum('type', ['movie', 'tv']);
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->string('poster_url')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('status', ['pending', 'imported', 'rejected'])->default('pending');
            $table->timestamps();

            $table->unique(['user_id', 'tmdb_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_requests');
    }
};
