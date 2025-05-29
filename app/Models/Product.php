<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['product_name', 'user_id', 'category_id', 'size', 'price', 'cover_photo', 'brand_id'];

    // Define the relationship with ProductImage model
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    // In Product.php model
    public function category()
    {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class);
    }


    public function brand() {

        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchandiseCartItems()
    {
        return $this->hasMany(MerchandiseCartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
}
