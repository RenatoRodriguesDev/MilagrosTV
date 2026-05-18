<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watched_items', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('item_type');
            $table->unsignedBigInteger('item_id');
            $table->timestamps();

            $table->unique(['session_id', 'item_type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watched_items');
    }
};
