<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Music;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MusicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('api_role', 'artist')->first();
        // Example music data to insert
        $musicData = [
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 1', 'music_title' => 'Updated Song Title', 'description' => 'This is a description of the song.', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [1, 2]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 2','music_title' => 'Updated Song Title', 'description' => 'Another description for the song.', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [3, 4]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 3', 'music_title' => 'Updated Song Title','description' => 'Description for song 3', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [1, 5]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 4', 'music_title' => 'Updated Song Title','description' => 'Description for song 4', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [2, 6]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 5','music_title' => 'Updated Song Title', 'description' => 'Description for song 5', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [4, 7]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 6','music_title' => 'Updated Song Title', 'description' => 'Description for song 6', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [3, 8]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 7','music_title' => 'Updated Song Title', 'description' => 'Description for song 7', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [2, 9]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 8', 'music_title' => 'Updated Song Title','description' => 'Description for song 8', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [5, 10]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 9', 'music_title' => 'Updated Song Title','description' => 'Description for song 9', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [4, 11]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 10', 'music_title' => 'Updated Song Title','description' => 'Description for song 10', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [3, 12]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 11','music_title' => 'Updated Song Title', 'description' => 'Description for song 11', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [5, 13]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 12','music_title' => 'Updated Song Title', 'description' => 'Description for song 12', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [6, 14]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 13','music_title' => 'Updated Song Title', 'description' => 'Description for song 13', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [7, 15]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 14','music_title' => 'Updated Song Title', 'description' => 'Description for song 14', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [8, 16]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 15','music_title' => 'Updated Song Title', 'description' => 'Description for song 15', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [9, 17]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 16', 'music_title' => 'Updated Song Title','description' => 'Description for song 16', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [10, 18]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 17','music_title' => 'Updated Song Title', 'description' => 'Description for song 17', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [11, 19]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 18','music_title' => 'Updated Song Title', 'description' => 'Description for song 18', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [12, 20]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 19','music_title' => 'Updated Song Title', 'description' => 'Description for song 19', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [13, 21]],
            ['user_id'=> $user->id,'artist_name' => 'Artist Name 20','music_title' => 'Updated Song Title', 'description' => 'Description for song 20', 'cover_photo' => 'https://shawnvincent.softvencefsd.xyz/uploads/cover_photos/updated-song-title.png', 'music_file' => 'https://shawnvincent.softvencefsd.xyz/uploads/music_files/harum-provident-dol.mp3', 'genre_ids' => [14, 22]],
        ];

        // Loop through the data and insert each entry
        foreach ($musicData as $data) {
            $music = Music::create([
                'user_id' => $data['user_id'],
                'artist_name' => $data['artist_name'],
                'music_title' => $data['music_title'],
                'description' => $data['description'],
                'cover_photo' => $data['cover_photo'],
                'music_file' => $data['music_file'],
            ]);

            // Attach genres to the music
            $music->genres()->attach($data['genre_ids']);
        }

    }
}
