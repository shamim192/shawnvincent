<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    protected $fillable = ['album_name', 'album_photo', 'user_id', 'artist_name', 'release_date'];

    // Relationship: An album belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Many-to-many relationship with music
    public function music()
    {
        return $this->belongsToMany(Music::class, 'album_song');
    }

    public function purchasers()
    {
        return $this->belongsToMany(User::class)->withPivot('purchased_at');
    }
}
