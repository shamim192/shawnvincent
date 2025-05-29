<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    // In Size.php model
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
