<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Music;
use App\Models\Product;
use App\Models\Favorite;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{
    use ApiResponse;

    // Search and Filter music
    public function searchAndFilter(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'search' => 'nullable|string|max:255',
            'genre' => 'nullable|string', // Genre filter
            'release' => 'nullable|string|in:most_recent,new_to_old,old_to_new', // Release date filter
        ]);

        $query = Music::query();

        // Apply search filter (search by title or artist)
        if ($request->has('search') && $request->search) {
            $query->where('music_title', 'like', '%' . $request->search . '%')
                ->orWhere('artist_name', 'like', '%' . $request->search . '%');
        }

        // Apply genre filter
        // Filter by genre if provided
        if ($request->has('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->genre . '%');
            });
        }

        // Apply release filter
        if ($request->has('release')) {
            switch ($request->release) {
                case 'most_recent':
                    $query->orderBy('created_at', 'desc'); // Show most recent first
                    break;
                case 'new_to_old':
                    $query->orderBy('created_at', 'asc'); // Show from newest to oldest
                    break;
                case 'old_to_new':
                    $query->orderBy('created_at', 'desc'); // Show from oldest to newest
                    break;
                default:
                    break;
            }
        }

        $user = auth('api')->user();

        if ($user) {
            $music = $query->with('genres')->where('user_id', '!=', $user->id)->paginate(12);
        } else {
            $music = $query->with('genres')->paginate(12);
        }

        return $this->success($music, 'Search and filter results retrieved successfully.', 200);
    }

    // Search music by title or artist genre

    public function searchMusic(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'search' => 'required|string|max:255',
        ]);

        $user = auth('api')->user();

        if ($user) {
            $music = Music::where('user_id', '!=', $user->id)->where('music_title', 'like', '%' . $request->search . '%')
                ->orWhere('artist_name', 'like', '%' . $request->search . '%')
                ->orWhereHas('genres', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                })
                ->with('genres') // Include genres in the result
                ->get();
        } else {
            $music = Music::where('music_title', 'like', '%' . $request->search . '%')
                ->orWhere('artist_name', 'like', '%' . $request->search . '%')
                ->orWhereHas('genres', function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                })
                ->with('genres') // Include genres in the result
                ->get();
        }



        return $this->success($music, 'Search results retrieved successfully.', 200);
    }

    public function searchAndFilterProduct(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string', // Category filter
            'price_range' => 'nullable|in:under_20,25_100,100_300,300_500,500_1000,1000_10000', // Price filter
        ]);

        $query = Product::query();

        // Apply search filter (search by product name)
        if ($request->has('search') && $request->search) {
            $query->where('product_name', 'like', '%' . $request->search . '%');
        }

        // Apply category filter
        if ($request->has('category') && $request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->category . '%');
            });
        }

        // Apply price range filter
        if ($request->has('price_range') && $request->price_range) {
            switch ($request->price_range) {
                case 'under_20':
                    $query->where('price', '<', 20);
                    break;
                case '25_100':
                    $query->whereBetween('price', [25, 100]);
                    break;
                case '100_300':
                    $query->whereBetween('price', [100, 300]);
                    break;
                case '300_500':
                    $query->whereBetween('price', [300, 500]);
                    break;
                case '500_1000':
                    $query->whereBetween('price', [500, 1000]);
                    break;
                case '1000_10000':
                    $query->whereBetween('price', [1000, 10000]);
                    break;
                default:
                    break;
            }
        }

        // Get the authenticated user
        $user = auth('api')->user();


        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            $products = $query->where('user_id', '!=', $user->id)->paginate(12);
        } else {
            // If the user is not authenticated, get all brands
            $products = $query->paginate(12);
        }

        // Check if the user has a favorite for the product
        if ($user) {
            foreach ($products as $product) {
                $product->is_favorite = Favorite::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists();
            }
        } else {
            foreach ($products as $product) {
                $product->is_favorite = false; // Default to false if user is not authenticated
            }
        }

        return $this->success($products, 'Search and filter results retrieved successfully.', 200);
    }

    public function searchProduct(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'search' => 'required|string|max:255',
        ]);

        // Get the authenticated user
        $user = auth('api')->user();

        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            // Search for products by name
            $products = Product::where('product_name', 'like', '%' . $request->search . '%')
                ->where('user_id', '!=', $user->id)
                ->with('brand') // Include brand in the result
                ->get();
        } else {
            // Search for products by name
            $products = Product::where('product_name', 'like', '%' . $request->search . '%')
                ->with('brand') // Include brand in the result
                ->get();
        }



        // Check if the user has a favorite for the product
        if ($user) {
            foreach ($products as $product) {
                $product->is_favorite = Favorite::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists();
            }
        } else {
            foreach ($products as $product) {
                $product->is_favorite = false; // Default to false if user is not authenticated
            }
        }

        return $this->success($products, 'Search results retrieved successfully.', 200);
    }
}
