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
        Schema::create('merchandise_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchandise_cart_id')->constrained('merchandise_carts')->onDelete('cascade');  // Link to merchandise_cart
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');  // Link to product
            $table->foreignId('size_id')->constrained('sizes')->onDelete('cascade');  // Link to size
            $table->integer('quantity')->default(1);  // Quantity of the product
            $table->decimal('price', 8, 2);  // Price of the product
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchandise_cart_items');
    }
};
