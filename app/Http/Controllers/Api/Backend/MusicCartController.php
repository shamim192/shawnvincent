<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Music;
use App\Models\MusicCart;
use Illuminate\Http\Request;
use App\Models\MusicCartItem;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class MusicCartController extends Controller
{
    use ApiResponse;
    // Add music to the cart
    public function addToCart(Request $request, $musicId)
    {
        // Validate the incoming request
        $request->validate([
            'quantity' => 'required|integer|min:1',  // Ensure valid quantity
        ]);

        // Find the music by ID
        $music = Music::find($musicId);
        if (!$music) {
            return $this->error([], 'Music not found', 404);
        }

        // Set the fixed price to 1 dollar for each music track
        $price = 1.00;  // Fixed price

        // Find or create a cart for the user
        $cart = MusicCart::firstOrCreate([
            'user_id' => auth('api')->id(),
        ]);

        // Check if this music is already in the cart (in music_cart_items)
        $cartItem = MusicCartItem::where('music_cart_id', $cart->id)
            ->where('music_id', $musicId)
            ->first();

        if ($cartItem) {
            return $this->success([], 'Music is already in the cart.', 200);
        } else {
            // Otherwise, create a new cart item with a fixed price
            MusicCartItem::create([
                'music_cart_id' => $cart->id,  // Link to the user's cart
                'music_id' => $musicId,        // Link to the music
                'quantity' => $request->quantity,  // Quantity of the music item
                'price' => $price,             // Fixed price for each music item
            ]);

            return $this->success([], 'Music added to the cart', 200);
        }
    }

    // Get the authenticated user's music cart
    public function getCart()
    {
        // Get the authenticated user
        $user = auth('api')->user();

        // Fetch all cart items for the user
        $cartItems = MusicCartItem::with('music') // Fetch the music details as well
            ->whereHas('musicCart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get();

        // Calculate the total price
        $total = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Return the cart items and total price
        return $this->success([
            'cart_items' => $cartItems,
            'total' => $total,
        ], 'Cart retrieved successfully', 200);
    }

    public function removeFromCart($musicId)
    {
        // Get the authenticated user
        $user = auth('api')->user();

        // Find the cart item for the user
        $cartItem = MusicCartItem::whereHas('musicCart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('music_id', $musicId)
            ->first();

        if (!$cartItem) {
            return $this->error([], 'Music item not found in the cart', 404);
        }

        // Delete the cart item
        $cartItem->delete();

        return $this->success([], 'Music item removed from cart', 200);
    }
}
