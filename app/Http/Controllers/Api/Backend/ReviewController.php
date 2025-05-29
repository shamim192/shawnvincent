<?php

namespace App\Http\Controllers\Api\Backend;

use App\Models\Order;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReviewController extends Controller
{
    use ApiResponse;

    public function addReview(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        // Check if the user has purchased the product and if the order status is 'completed'
        $order = Order::where('user_id', auth('api')->id())
            ->where('status', 'completed')  // Check if the order status is 'completed' (or the appropriate status for a completed order)
            ->whereHas('orderItems', function ($query) use ($productId) {
                $query->where('product_id', $productId);  // Check if the order contains the product
            })->first();

        if (!$order) {
            return $this->error([],'You must purchase the product before leaving a review.', 403);
        }

        // Create the review
        $review = new Review();
        $review->product_id = $productId;
        $review->user_id = auth('api')->id();
        $review->rating = $request->rating;
        $review->review = $request->review;
        $review->save();

        return $this->success($review, 'Review added successfully', 201);
    }

    public function getProductReviews($productId)
    {
        $reviews = Review::where('product_id', $productId)->with('user')->get();

        return $this->success($reviews, 'Product reviews retrieved successfully', 200);
    }
}
