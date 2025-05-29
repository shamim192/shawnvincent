<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\MerchandiseCart;
use App\Models\MerchandiseCartItem;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MerchandiseCartController extends Controller
{
    use ApiResponse;
    // Add product to the cart
    public function addToCart(Request $request, $productId)
    {
        // Validate the incoming request
        $request->validate([
            'quantity' => 'required|integer|min:1',  // Ensure valid quantity
            'size_id' => 'required|exists:sizes,id',  // Ensure valid size_id
        ]);

        // Find the product
        $product = Product::findOrFail($productId);

        // Find or create a cart for the user
        $cart = MerchandiseCart::firstOrCreate([
            'user_id' => auth('api')->id(),  // Get the authenticated user's ID
        ]);

        // Check if the product already exists in the cart (in merchandise_cart_items)
        $cartItem = MerchandiseCartItem::where('merchandise_cart_id', $cart->id)
            ->where('product_id', $productId)
            ->where('size_id', $request->size_id)  // Check for the specific size_id
            ->first();

        if ($cartItem) {
            // If the product is already in the cart, update the quantity
            $cartItem->update([
                'quantity' => $cartItem->quantity + $request->quantity,  // Increment the quantity
            ]);
            return $this->success([], 'Product quantity updated in cart', 200);
        } else {
            // Otherwise, create a new cart item
            MerchandiseCartItem::create([
                'merchandise_cart_id' => $cart->id,   // Link to user's cart
                'product_id' => $productId,            // Link to product
                'quantity' => $request->quantity,      // Quantity of product
                'price' => $product->price,            // Product price
                'size_id' => $request->size_id,        // Correct size_id reference
            ]);

            return $this->success([], 'Product added to the cart', 200);
        }
    }


    // Get the authenticated user's merchandise cart
    public function getCart()
    {
        // Get the authenticated user
        $user = auth('api')->user();

        // Fetch all cart items for the user
        $cartItems = MerchandiseCartItem::with('product') // Fetch the related product details as well
            ->whereHas('merchandiseCart', function ($query) use ($user) {
                $query->where('user_id', $user->id);  // Get cart items for the authenticated user
            })
            ->get();

        // If the cart is empty, return a message saying the cart is empty
        if ($cartItems->isEmpty()) {
            return $this->success([], 'Your cart is empty.', 200);
        }

        // Calculate the total price of the cart items
        $total = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;  // Price * quantity for each cart item
        });

        // Return the cart items and total price
        return $this->success([
            'cart_items' => $cartItems,  // All cart items with product details
            'total' => $total,           // Total price of the cart
        ], 'Cart retrieved successfully', 200);
    }

    // Remove product from cart
    public function removeFromCart(Request $request, $productId)
    {
        // Validate the incoming request for size_id
        $request->validate([
            'size_id' => 'required|exists:sizes,id',  // Ensure valid size_id
        ]);

        // Get the authenticated user
        $user = auth('api')->user();

        // Find the user's cart and the specific cart item based on product_id and size_id
        $cartItem = MerchandiseCartItem::where('product_id', $productId)
            ->where('size_id', $request->size_id)
            ->whereHas('merchandiseCart', function ($query) use ($user) {
                $query->where('user_id', $user->id);  // Ensure the cart belongs to the authenticated user
            })
            ->first();

        // If the cart item is not found, return an error message
        if (!$cartItem) {
            return $this->error([], 'Product with the selected size not found in the cart', 404);
        }

        // Remove the cart item from the cart
        $cartItem->delete();

        // Return a success message
        return $this->success([], 'Cart item removed successfully', 200);
    }


    // Update the quantity of a product in the cart
    public function updateCartQuantity(Request $request, $productId)
    {
        // Validate the incoming request
        $request->validate([
            'action' => 'required|in:increase,decrease',  // Ensure valid action
            'size_id' => 'required|exists:sizes,id',      // Ensure valid size_id
        ]);

        // Get the authenticated user
        $user = auth('api')->user();

        $cartItem = MerchandiseCartItem::where('product_id', $productId)
            ->where('size_id', $request->size_id)
            ->whereHas('merchandiseCart', function ($query) use ($user) {
                $query->where('user_id', $user->id);  // Ensure the cart belongs to the authenticated user
            })
            ->first();

        if (!$cartItem) {
            return $this->error([], 'Product with the selected size not found in the cart', 404);
        }

        // Adjust the quantity based on the action
        if ($request->action == 'increase') {
            $cartItem->increment('quantity');  // Increase by 1
        } elseif ($request->action == 'decrease') {
            if ($cartItem->quantity > 1) {
                $cartItem->decrement('quantity');  // Decrease by 1, but not below 1
            }
        }

        // Return the updated cart item with new quantity and price
        return $this->success([
            'cart_id' => $cartItem->merchandise_cart_id,  // Include cart_id in response
            'product_id' => $cartItem->product_id,
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->price,
            'size_id' => $cartItem->size_id,  // Include size_id in response
        ], 'Cart quantity updated successfully', 200);
    }
}
