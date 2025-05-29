<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Album;
use App\Models\Genre;
use App\Models\Music;
use App\Helpers\Helper;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\MusicOrderItem;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class MusicController extends Controller
{
    use ApiResponse;

    public function uploadMusic(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'music_title' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'description' => 'required|string',
            'release_date' => 'required|date',
            'genre_ids' => 'required|array', // Expecting an array of genre IDs
            'genre_ids.*' => 'exists:genres,id', // Each genre ID must exist in the genres table
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048',
            'music_file' => 'required|mimes:mp3,wav,aac|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Handle the cover photo uploagetAllMusicd if it exists
        $coverPhotoPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPhotoPath = Helper::fileUpload($request->file('cover_photo'), 'cover_photos', $request->input('music_title'));
        }

        // Handle the music file upload
        $musicFilePath = Helper::fileUpload($request->file('music_file'), 'music_files', $request->input('music_title'));

        // Save the music data in the database
        $music = new Music();
        $music->music_title = $request->input('music_title');
        $music->artist_name = $request->input('artist_name');
        $music->description = $request->input('description');
        $music->release_date = $request->input('release_date');
        $music->cover_photo = asset($coverPhotoPath); // Store the URL of the cover photo
        $music->music_file = asset($musicFilePath); // Store the URL of the music file
        $music->user_id = auth('api')->id(); // Assuming the user is authenticated
        $music->save();

        // Attach the selected genres to the music
        $music->genres()->attach($request->input('genre_ids'));

        return $this->success($music, 'Music uploaded successfully.', 201);
    }

    public function updateMusic(Request $request, $id)
    {
        // Find the existing music record by ID
        $music = Music::find($id);

        // If music not found, return error
        if (!$music) {
            return $this->error([], 'Music not found', 404);
        }

        // Check if the authenticated user is the owner of the music
        if ($music->user_id !== auth('api')->id()) {
            return $this->error([], 'You are not authorized to update this music.', 403);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'music_title' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'description' => 'required|string',
            'release_date' => 'required|date',
            'genre_ids' => 'required|array', // Ensure genre_ids is an array of genre IDs
            'genre_ids.*' => 'exists:genres,id', // Each genre ID must exist in the genres table
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048', // Cover photo validation (optional)
            'music_file' => 'nullable|mimes:mp3,wav,aac|max:10000', // Music file validation (optional)
        ]);

        // If validation fails, return error messages
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Handle the cover photo upload if it exists (update if new photo is uploaded)
        $coverPhotoPath = $music->cover_photo; // Default to existing cover photo
        if ($request->hasFile('cover_photo')) {
            // Delete the old cover photo if a new one is uploaded
            if ($coverPhotoPath && file_exists(public_path($coverPhotoPath))) {
                Helper::fileDelete(public_path($coverPhotoPath)); // Use fileDelete helper
            }
            // Upload new cover photo
            $coverPhotoPath = Helper::fileUpload($request->file('cover_photo'), 'cover_photos', $request->input('music_title'));
        }

        // Handle the music file upload if it exists (update if new file is uploaded)
        $musicFilePath = $music->music_file; // Default to existing music file
        if ($request->hasFile('music_file')) {
            // Delete the old music file if a new one is uploaded
            if ($musicFilePath && file_exists(public_path($musicFilePath))) {
                Helper::fileDelete(public_path($musicFilePath)); // Use fileDelete helper
            }
            // Upload new music file
            $musicFilePath = Helper::fileUpload($request->file('music_file'), 'music_files', $request->input('music_title'));
        }

        // Update the music record with new data
        $music->music_title = $request->input('music_title');
        $music->artist_name = $request->input('artist_name');
        $music->description = $request->input('description');
        $music->release_date = $request->input('release_date');
        $music->cover_photo = url($coverPhotoPath); // Store the full URL of the cover photo
        $music->music_file = url($musicFilePath); // Store the full URL of the music file
        $music->save(); // Save the updated record

        // Sync genres (update the genres relationship with new genre IDs)
        $music->genres()->sync($request->input('genre_ids')); // This will attach the new genres and remove any old ones that are not in the array

        // Return success response with the updated music data
        return $this->success($music, 'Music updated successfully.', 200);
    }

    // Method to create a new album
    public function addAlbum(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'album_name' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'album_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048', // Cover photo validation
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Handle album photo upload if it exists
        $albumPhotoPath = null;
        if ($request->hasFile('album_photo')) {
            $albumPhotoPath = Helper::fileUpload($request->file('album_photo'), 'album_photos', $request->input('album_name'));
        }

        // Save the album data
        $album = new Album();
        $album->album_name = $request->input('album_name');
        $album->artist_name = $request->input('artist_name');
        $album->release_date = $request->input('release_date');
        $album->album_photo = $albumPhotoPath ? asset($albumPhotoPath) : null;
        $album->user_id = auth('api')->id(); // Associate with the authenticated user
        $album->save();

        return $this->success($album, 'Album created successfully.', 201);
    }

    // Update an existing album
    public function updateAlbum(Request $request, $albumId)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'album_name' => 'required|string|max:255',
            'artist_name' => 'required|string|max:255',
            'release_date' => 'required|date',
            'album_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:20048', // Cover photo validation
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Find the existing album
        $album = Album::find($albumId);

        if (!$album) {
            return $this->error([], 'Album not found', 404);
        }

        // Handle album photo upload if it exists
        $albumPhotoPath = $album->album_photo;
        if ($request->hasFile('album_photo')) {
            // Delete the old album photo if a new one is uploaded
            if ($albumPhotoPath && file_exists(public_path($albumPhotoPath))) {
                Helper::fileDelete(public_path($albumPhotoPath)); // Use fileDelete helper
            }
            // Upload new album photo
            $albumPhotoPath = Helper::fileUpload($request->file('album_photo'), 'album_photos', $request->input('album_name'));
        }

        // Update the album data
        $album->album_name = $request->input('album_name');
        $album->artist_name = $request->input('artist_name');
        $album->release_date = $request->input('release_date');
        $album->album_photo = asset($albumPhotoPath); // Store the URL of the album photo
        $album->save();

        return $this->success($album, 'Album updated successfully.', 200);
    }

    // Get album details with music tracks
    public function getAlbumDetails($albumId)
    {
        $user = auth('api')->user();

        // Fetch the album with music and genres
        $album = Album::with(['music.genres'])->find($albumId);

        if (!$album) {
            return $this->error([], 'Album not found', 404);
        }

        // Check if user has purchased the album
        $isPurchased = false;
        if ($user) {
            $isPurchased = $user->purchasedAlbums()
                ->wherePivot('album_id', $album->id)
                ->wherePivot('status', 'completed')
                ->exists();
        }

        // Attach purchase flag to each track
        $album->music->transform(function ($track) use ($isPurchased) {
            $track->purchased = $isPurchased;
            return $track;
        });

        // Optionally attach purchase flag at album level
        $album->purchased = $isPurchased;

        return $this->success($album, 'Album details fetched successfully.', 200);
    }

    // Add music to an album
    public function addMusicToAlbum(Request $request, $albumId)
    {
        // Validate incoming request
        $request->validate([
            'music_ids' => 'required|array',
            'music_ids.*' => 'exists:music,id', // Ensure all music IDs exist in the music table
        ]);

        // Find the album by ID
        $album = Album::find($albumId);

        if (!$album) {
            return $this->error([], 'Album not found', 404);
        }

        // Attach music to the album
        $album->music()->attach($request->input('music_ids')); // `sync` will add or remove the selected music

        return $this->success($album, 'Music added to the album successfully.', 200);
    }


    // get all list of music of the album
    public function getAlbumMusic($albumId)
    {
        // Fetch the album with its associated music for the authenticated user
        $album = Album::where('user_id', auth('api')->id())  // Ensure the album belongs to the authenticated user
            ->with(['music' => function ($query) {
                $query->where('user_id', auth('api')->id());  // Ensure the music belongs to the authenticated user
            }])
            ->find($albumId);

        if (!$album) {
            return $this->error([], 'Album not found or you do not have permission to view this album.', 404);
        }

        return $this->success($album, 'Album music fetched successfully.', 200);
    }


    public function deleteAlbumMusic($albumId, $musicId)
    {
        // Find the album for the authenticated user
        $album = Album::where('user_id', auth('api')->id())->find($albumId);

        if (!$album) {
            return $this->error([], 'Album not found or you do not have permission to access this album.', 404);
        }

        // Find the music (song) in the music table
        $music = Music::find($musicId);

        if (!$music) {
            return $this->error([], 'Music not found.', 404);
        }

        // Check if the music is associated with the album
        if (!$album->music->contains($music)) {
            return $this->error([], 'Music is not associated with this album.', 404);
        }

        // Remove the music from the album (detach it)
        $album->music()->detach($musicId);

        return $this->success([], 'Music removed from the album successfully.', 200);
    }

    // Get all albums for the authenticated user
    public function getAlbumList()
    {
        // Fetch all albums for the authenticated user
        $albums = Album::where('user_id', auth('api')->id())->get();

        if ($albums->isEmpty()) {
            // return $this->error([], 'No albums found for the authenticated user.', 404);
            return $this->success([], 'No albums found for the authenticated user.', 200);
        }
        return $this->success($albums, 'Album list fetched successfully.', 200);
    }

    // get all music list

    public function getAllMusic()
    {
        // Fetch all music for the authenticated user
        $music = Music::where('user_id', auth('api')->id())->get();

        if ($music->isEmpty()) {
            return $this->error([], 'No music found.', 404);
        }

        return $this->success($music, 'Music list fetched successfully.', 200);
    }

    // Get music details
    public function getMusicDetails($id)
    {
        // Find the music by ID
        $music = Music::with('genres')->find($id);

        if (!$music) {
            return $this->error([], 'Music not found.', 404);
        }

        $user = auth('api')->user();
        $purchased = false;

        if ($user) {
            $purchased = MusicOrderItem::where('music_id', $id)
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('status', 'completed');
                })
                ->exists();
        }

        $music->purchased = $purchased;

        return $this->success($music, 'Music details fetched successfully.', 200);
    }

    // Get genre list
    public function getGenreList()
    {
        // Fetch all genres
        $genres = Genre::all();

        if ($genres->isEmpty()) {
            return $this->error([], 'No genres found.', 404);
        }

        return $this->success($genres, 'Genre list fetched successfully.', 200);
    }

    // Delete music

    public function deleteMusic($id)
    {
        // Find the music by ID
        $music = Music::find($id);

        if (!$music) {
            return $this->error([], 'Music not found.', 404);
        }
        // Delete the music
        $music->delete();

        return $this->success([], 'Music deleted successfully.', 200);
    }

    // get new music list

    public function getNewMusic()
    {
        $user = auth('api')->user();

        if ($user) {
            $music = Music::with('genres')->where('user_id', '!=', $user->id)->orderBy('created_at', 'desc')
                ->take(6) // Limit to 10 new music
                ->get();
        } else {
            $music = Music::with('genres')->orderBy('created_at', 'desc')
                ->take(6) // Limit to 10 new music
                ->get();
        }


        if ($music->isEmpty()) {
            return $this->success([], 'No music found.', 200);
        }

        return $this->success($music, 'Music list fetched successfully.', 200);
    }

    public function getAllGlobalMusic()
    {
        $user = auth('api')->user();

        if ($user) {
            $music = Music::with('genres')->where('user_id', '!=', $user->id)->orderBy('created_at', 'desc')
                ->paginate(12);
        } else {
            $music = Music::with('genres')->orderBy('created_at', 'desc')
                ->paginate(12);
        }


        if ($music->isEmpty()) {
           return $this->success([], 'No music found.', 200);
        }

        return $this->success($music, 'Music list fetched successfully.', 200);
    }

    public function getAllGlobalAlbum()
    {
        $user = auth('api')->user();

        if ($user) {
            $music = Album::with('music')->where('user_id', '!=', $user->id)->orderBy('created_at', 'desc')
                ->paginate(12); // Limit to 12 new music

        } else {
            $music = Album::with('music')->orderBy('created_at', 'desc')
                ->paginate(12); // Limit to 12 new music

        }


        if ($music->isEmpty()) {
            return $this->success([], 'No music found.', 200);
        }

        return $this->success($music, 'Music list fetched successfully.', 200);
    }

    public function getYouMayLikeMusic()
    {
        $user = auth('api')->user();

        if ($user) {
            $music = Music::with('genres')->where('user_id', '!=', $user->id)->orderBy('created_at', 'desc')
                ->inRandomOrder()
                ->take(10) // Limit to 10 new music
                ->get(); // Limit to 12 new music
        } else {
            $music = Music::with('genres')->orderBy('created_at', 'desc')
                ->inRandomOrder()
                ->take(10) // Limit to 10 new music
                ->get(); // Limit to 12 new music
        }


        if ($music->isEmpty()) {
            return $this->success([], 'No music found.', 200);
        }

        return $this->success($music, 'Music list fetched successfully.', 200);
    }

    public function getMyMusicDetails($id)
    {
        // Find the music by ID
        $music = Music::with('genres')->where('user_id', auth('api')->id())->find($id);

        if (!$music) {
            return $this->error([], 'Music not found.', 404);
        }

        return $this->success($music, 'Music details fetched successfully.', 200);
    }

    public function getMyAllMusic()
    {
        // Fetch all music for the authenticated user
        $music = Music::with('genres')->where('user_id', auth('api')->id())->paginate(12);

        if ($music->isEmpty()) {
            return $this->error([], 'No music found.', 404);
        }

        return $this->success($music, 'Music list fetched successfully.', 200);
    }

    public function getMyAlbumDetails($id)
    {
        // Find the music by ID
        $album = Album::with('music')->where('user_id', auth('api')->id())->find($id);

        if (!$album) {
            return $this->error([], 'Album not found.', 404);
        }

        return $this->success($album, 'Album details fetched successfully.', 200);
    }

    public function getTopArtist()
    {
        // $artists = DB::table('music_order_items')
        //     ->join('music', 'music.id', '=', 'music_order_items.music_id')
        //     ->join('users', 'users.id', '=', 'music.user_id')
        //     ->select(
        //         'users.id',
        //         'users.first_name',
        //         'users.last_name',
        //         'users.avatar',
        //         DB::raw('COUNT(music_order_items.id) as track_sales')
        //     )
        //     ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.avatar')
        //     ->orderByDesc('track_sales')
        //     ->limit(10)
        //     ->get()
        //     ->map(function ($artist) {
        //         $artist->avatar = $artist->avatar ? asset($artist->avatar) : null;
        //         return $artist;
        //     });

        // 1. Track sales grouped by music.user_id
        $trackSales = DB::table('music_order_items')
            ->join('music', 'music.id', '=', 'music_order_items.music_id')
            ->select('music.user_id', DB::raw('COUNT(*) as track_sales'))
            ->groupBy('music.user_id');

        // 2. Album sales grouped by albums.user_id (album owner is the artist)
        $albumSales = DB::table('album_user')
            ->join('albums', 'albums.id', '=', 'album_user.album_id')
            ->select('albums.user_id', DB::raw('COUNT(album_user.id) as album_sales'))
            ->where('album_user.status', 'completed')
            ->groupBy('albums.user_id');

        // 3. Merge with users and return clean fields only
        $artists = DB::table('users')
            ->leftJoinSub($trackSales, 'track_sales', 'users.id', '=', 'track_sales.user_id')
            ->leftJoinSub($albumSales, 'album_sales', 'users.id', '=', 'album_sales.user_id')
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                'users.avatar',
                DB::raw('COALESCE(track_sales.track_sales, 0) as track_sales')
            )
            ->orderByDesc('track_sales')
            ->limit(10)
            ->get()
            ->map(function ($artist) {
                $artist->avatar = $artist->avatar ? asset($artist->avatar) : null;
                return $artist;
            });

        if ($artists->isEmpty()) {
            return $this->error([], 'No artists found.', 404);
        }
        return $this->success($artists, 'Top artists fetched successfully.', 200);
    }

    public function downloadMusic($id)
    {
        $music = Music::findOrFail($id);

        $user = auth('api')->user();

        // Check purchase
        $hasPurchased = MusicOrderItem::where('music_id', $id)
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'completed');
            })
            ->exists();

        if (!$hasPurchased) {
            return response()->json(['error' => 'Please buy this music to download'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Download link generated',
            'data' => [
                'download_url' => $music->music_file // or $music->file_path or whatever your column is
            ]
        ], 200);
    }
}
