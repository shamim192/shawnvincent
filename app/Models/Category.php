<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'created_by',
        'image',
        'status',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
    }

    // In Category.php model
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
