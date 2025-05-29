<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MerchandiseCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
    ];

    // Relationship with Cart Items
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchandiseCartItems()
    {
        return $this->hasMany(MerchandiseCartItem::class);
    }

    public function getTotalAttribute()
    {
        return $this->merchandiseCartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }
}
