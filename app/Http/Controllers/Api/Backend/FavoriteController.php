<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Product;
use App\Models\Favorite;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FavoriteController extends Controller
{
    use ApiResponse;

    public function toggleFavorite(Request $request, $productId)
    {
        // Get the authenticated user
        $user = auth('api')->user();

        // Find the product
        $product = Product::find($productId);

        if (!$product) {
            return $this->success([], 'Product not found', 200);
        }

        // Check if the product is already favorited by the user
        $favorite = Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($favorite) {
            // If the product is already in favorites, remove it
            $favorite->delete();

            return $this->success([], 'Product removed from favorites', 200);
        } else {
            // Otherwise, add it to favorites
            Favorite::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return $this->success([], 'Product added to favorites', 200);
        }
    }

    public function getFavorites(Request $request)
    {
        // Get the authenticated user
        $user = auth('api')->user();

        // Fetch the user's favorite products and load the associated product details
        $favorites = Favorite::where('user_id', $user->id)
            ->with('product')  // Eager load the associated product details
            ->get()
            ->pluck('product');  // Extract only the product details

            if($user) {
                foreach ($favorites as $product) {
                    $product->is_favorite = Favorite::where('user_id', $user->id)
                        ->where('product_id', $product->id)
                        ->exists();
                }
            } else {
                foreach ($favorites as $product) {
                    $product->is_favorite = false; // Default to false if user is not authenticated
                }
            }


        return $this->success($favorites, 'Favorite products retrieved successfully', 200);
    }
}
