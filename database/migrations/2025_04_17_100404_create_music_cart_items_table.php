<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('music_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('music_cart_id')->constrained('music_carts')->onDelete('cascade');  // Link to music_cart
            $table->foreignId('music_id')->constrained('music')->onDelete('cascade');  // Link to music
            $table->integer('quantity')->default(1);  // Quantity of the music item
            $table->decimal('price', 8, 2);  // Price of the music item
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('music_cart_items');
    }
};
