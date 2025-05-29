<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusicOrderItem extends Model
{
    protected $fillable = [
        'music_order_id', 'music_id', 'quantity', 'price'
    ];

    public function order()
    {
        return $this->belongsTo(MusicOrder::class, 'music_order_id');
    }

    public function music()
    {
        return $this->belongsTo(Music::class);
    }
}
