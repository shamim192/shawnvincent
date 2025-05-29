<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{

    protected $fillable = [
        'name',
    ];
    // Define the many-to-many relationship with Music
    public function musics()
    {
        return $this->belongsToMany(Music::class, 'genre_music');
    }
}
