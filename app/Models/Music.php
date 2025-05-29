<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Music extends Model
{
    protected $fillable = [
        'music_title',
        'artist_name',
        'description',
        'release_date',
        'cover_photo',
        'music_file',
        'user_id',
    ];

    // Many-to-many relationship with albums
    public function albums()
    {
        return $this->belongsToMany(Album::class, 'album_song');
    }

     // Define the many-to-many relationship with Genre
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'genre_music');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
