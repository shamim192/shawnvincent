<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path'];

    // Define the inverse relationship (each image belongs to a product)
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
