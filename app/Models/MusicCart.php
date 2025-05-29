<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MusicCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',  // User who owns the cart
        'music_id'
    ];

    // Relationship with Cart Items
    public function musicCartItems()
    {
        return $this->hasMany(MusicCartItem::class, 'music_cart_id');
    }
}
