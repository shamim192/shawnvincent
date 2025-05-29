<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MusicCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'music_cart_id',  // The cart this item belongs to
        'music_id',       // The music track being added
        'quantity',       // Quantity of the music item (if required)
        'price',          // Price of the music track
    ];

    // Relationship with Music
    public function music()
    {
        return $this->belongsTo(Music::class);
    }

    // Relationship with MusicCart
    public function musicCart()
    {
        return $this->belongsTo(MusicCart::class);
    }
}
