<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MerchandiseCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchandise_cart_id',  // The cart this item belongs to
        'product_id',           // The product being added
        'quantity',             // Quantity of the product
        'price',                // Price of the product
        'size_id',              // Size of the product
    ];

    // Relationship with Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relationship with MerchandiseCart
    public function merchandiseCart()
    {
        return $this->belongsTo(MerchandiseCart::class);
    }
}
