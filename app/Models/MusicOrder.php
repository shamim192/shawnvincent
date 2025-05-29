<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MusicOrder extends Model
{
    protected $fillable = [
        'user_id', 'total', 'status', 'checkout_session_id', 'checkout_url', 'paid_at'
    ];

    public function items()
    {
        return $this->hasMany(MusicOrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
