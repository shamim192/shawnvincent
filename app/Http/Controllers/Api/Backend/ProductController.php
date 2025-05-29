<?php

namespace App\Http\Controllers\Api\Backend;

use Carbon\Carbon;
use App\Models\Size;
use App\Models\Brand;
use App\Models\Order;
use App\Helpers\Helper;
use App\Models\Product;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\OrderItem;
use App\Traits\ApiResponse;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ApiResponse;
    public function addProduct(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id', // Validating category ID
            'sizes' => 'required|array', // Validate that sizes is an array
            'sizes.*' => 'exists:sizes,id', // Validating that each size exists in the sizes table
            'price' => 'required|numeric',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Cover photo validation
            'additional_photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Multiple images validation
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Handle the cover photo upload if it exists
        $coverPhotoPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPhotoPath = Helper::fileUpload($request->file('cover_photo'), 'cover_photos', $request->input('product_name'));
        }

        $user = auth('api')->user(); // Assuming the user is authenticated

        $brand = $user->brand;


        // Create the product record
        $product = Product::create([
            'product_name' => $request->input('product_name'),
            'category_id' => $request->input('category_id'), // Store category ID
            'price' => $request->input('price'),
            'user_id' => auth('api')->user()->id, // Assuming the user is authenticated
            'cover_photo' => $coverPhotoPath ? asset($coverPhotoPath) : null,
            'brand_id' => $brand->id, // Assuming the brand is associated with the authenticated user
        ]);

        // Attach the selected sizes to the product (many-to-many relationship)
        $product->sizes()->attach($request->input('sizes')); // Sync sizes with the pivot table

        // Handle multiple additional photos upload if they exist
        if ($request->hasFile('additional_photos')) {
            foreach ($request->file('additional_photos') as $photo) {
                $photoPath = Helper::fileUpload($photo, 'additional_photos', $request->input('product_name'));

                // Save each image in the product_images table
                ProductImage::create([
                    'product_id' => $product->id, // Associate the image with the product
                    'image_path' => asset($photoPath), // Store the full URL
                ]);
            }
        }

        // Return success response
        return $this->success($product, 'Product uploaded successfully.', 201);
    }

    public function updateProduct(Request $request, $id)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id', // Validating category ID
            'sizes' => 'required|array', // Validate that sizes is an array
            'sizes.*' => 'exists:sizes,id', // Validating that each size exists in the sizes table
            'price' => 'required|numeric',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:8048', // Cover photo validation
            'additional_photos.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048', // Multiple images validation
            'remove_photos' => 'nullable|array', // Array of photo IDs to remove
            'remove_photos.*' => 'exists:product_images,id', // Validating that each ID exists in the product_images table
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Get the authenticated user
        $user = auth('api')->user(); // Assuming the user is authenticated

        // Find the product by ID
        $product = Product::find($id);

        if (!$product) {
            return $this->success([], 'Product not found.', 200);
        }

        // Then verify the product belongs to the authenticated user
        if ($product->user_id != $user->id) {
            return $this->error('Unauthorized: This product does not belong to you.', 403);
        }


        // Start updating the product information
        $product->product_name = $request->input('product_name');
        $product->category_id = $request->input('category_id');
        $product->price = $request->input('price');

        // Handle the cover photo upload if it exists and update if present
        if ($request->hasFile('cover_photo')) {
            // Delete the old cover photo from the storage (optional)
            if ($product->cover_photo) {
                Helper::fileDelete($product->cover_photo); // Assuming Helper::deleteFile() will handle the deletion logic
            }

            // Upload the new cover photo
            $coverPhotoPath = Helper::fileUpload($request->file('cover_photo'), 'cover_photos', $request->input('product_name'));
            $product->cover_photo = asset($coverPhotoPath);
        }

        // Save the updated product data
        $product->save();

        // Update the sizes (many-to-many relationship)
        $product->sizes()->sync($request->input('sizes')); // Sync the sizes to update them

        // Handle the removal of old additional photos
        if ($request->has('remove_photos')) {
            $removePhotoIds = $request->input('remove_photos');
            foreach ($removePhotoIds as $photoId) {
                // Find and delete the associated image from the database and storage
                $productImage = ProductImage::find($photoId);
                if ($productImage) {
                    Helper::fileDelete($productImage->image_path); // Delete the file
                    $productImage->delete(); // Remove the record from the database
                }
            }
        }


        // Handle multiple additional photos upload if they exist
        if ($request->hasFile('additional_photos')) {
            foreach ($request->file('additional_photos') as $photo) {
                // Handle additional photo upload
                $photoPath = Helper::fileUpload($photo, 'additional_photos', $request->input('product_name'));

                // Save each image in the product_images table
                ProductImage::create([
                    'product_id' => $product->id, // Associate the image with the product
                    'image_path' => asset($photoPath), // Store the full URL
                ]);
            }
        }

        // Return success response
        return $this->success($product, 'Product updated and stored successfully.', 200);
    }

    public function getBrandList()
    {

        // Check if the user is authenticated
        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            // Query for brands, excluding the user's own brand
            $brandList = Brand::where('user_id', '!=', $user->id)->take(10)->get();
        } else {
            // If the user is not authenticated, get all brands
            $brandList = Brand::take(10)->get();
        }

        if ($brandList->isEmpty()) {
            return $this->success([], 'No brands found for this user.', 200);
        }

        if ($brandList) {
            return $this->success($brandList, 'Brand retrieved successfully.', 200);
        } else {
            return $this->success([], 'Brand not found.', 200);
        }
    }

    public function getTopBrandList()
    {
        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            $topBrandList = Brand::select('id', 'brand_image', 'name')->where('user_id', '!=', $user->id)->take(10)->get();
        } else {
            // If the user is not authenticated, get all brands
            $topBrandList = Brand::select('id', 'brand_image', 'name')->take(10)->get();
        }

        if ($topBrandList->isEmpty()) {
            return $this->success([], 'No top brands found for this user.', 200);
        }

        if ($topBrandList) {
            return $this->success($topBrandList, 'Top brand retrieved successfully.', 200);
        } else {
            return $this->success([], 'Top brand not found.', 200);
        }
    }

    public function getAllBrandList()
    {

        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            $allBrandList = Brand::where('user_id', '!=', $user->id)->paginate(12);
        } else {
            // If the user is not authenticated, get all brands
            $allBrandList = Brand::paginate(12);
        }


        if ($allBrandList->isEmpty()) {
            return $this->success([], 'No all brands found for this user.', 200);
        }

        if ($allBrandList) {
            return $this->success($allBrandList, 'All brand retrieved successfully.', 200);
        } else {
            return $this->success([], 'All brand not found.', 200);
        }
    }

    public function getBrandProductDetails($id)
    {
        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            $brand = Brand::where('user_id', '!=', $user->id)->find($id);
        } else {
            // If the user is not authenticated, get all brands
            $brand = Brand::find($id);
        }

        if (!$brand) {
            return $this->error([], 'Brand not found.', 403);
        }

        return $this->success($brand, 'Brand product details retrieved successfully.', 200);
    }

    public function getProductDetails($id)
    {
        $product = Product::with(['sizes', 'images', 'category'])->find($id);

        if (!$product) {
            return $this->error([], 'Product not found.', 403);
        }

        // Get the authenticated user
        $user = auth('api')->user();

        // Check if the user has a favorite for the product
        if ($user) {
            $product->is_favorite = Favorite::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();
        } else {

            $product->is_favorite = false; // Default to false if user is not authenticated

        }

        return $this->success($product, 'Product details retrieved successfully.', 200);
    }

    public function updateBrand(Request $request, $id)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'brand_name' => 'required|string|max:255',
            'brand_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048', // Brand image validation
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10048', // Cover image validation
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        // Find the brand by ID
        $brand = Brand::find($id);

        if (!$brand) {
            return $this->success([], 'Brand not found.', 200);
        }

        // Handle the brand image upload if it exists
        if ($request->hasFile('brand_image')) {
            // Delete the old brand image from the storage (optional)
            if ($brand->brand_image) {
                Helper::fileDelete($brand->brand_image); // Assuming Helper::deleteFile() will handle the deletion logic
            }

            // Upload the new brand image
            $brandImagePath = Helper::fileUpload($request->file('brand_image'), 'brands', $request->input('brand_name'));
            $brand->brand_image = asset($brandImagePath);
        }

        // Handle the cover image upload if it exists
        if ($request->hasFile('cover_image')) {
            // Delete the old cover image from the storage (optional)
            if ($brand->cover_image) {
                Helper::fileDelete($brand->cover_image); // Assuming Helper::deleteFile() will handle the deletion logic
            }

            // Upload the new cover image
            $coverImagePath = Helper::fileUpload($request->file('cover_image'), 'brands', $request->input('brand_name'));
            $brand->cover_image = asset($coverImagePath);
        }

        // Update the brand name
        $brand->name = $request->input('brand_name');

        // Save the updated brand data
        $brand->save();

        // Return success response
        return $this->success($brand, 'Brand updated successfully.', 200);
    }

    public function getCategoryList()
    {
        $categoryList = Category::all();

        if ($categoryList->isEmpty()) {
            return $this->success([], 'No categories found.', 200);
        }

        return $this->success($categoryList, 'Categories retrieved successfully.', 200);
    }
    public function getSizeList()
    {
        $sizeList = Size::all();

        if ($sizeList->isEmpty()) {
            return $this->success([], 'No sizes found.', 200);
        }

        return $this->success($sizeList, 'Sizes retrieved successfully.', 200);
    }

    public function getAllProduct(Request $request, $brandId)
    {
        // Check if the brand ID is valid
        $brand = Brand::find($brandId);

        if (!$brand) {
            return $this->error([], 'Brand not found.', 200);
        }

        // Start building the query for products under the specified brand
        $allProduct = Product::where('brand_id', $brandId);

        if (auth('api')->check()) {
            // Get the authenticated user
            $user = auth('api')->user();

            $allProduct = Product::where('brand_id', $brandId)->where('user_id', '!=', $user->id);
        } else {
            // If the user is not authenticated, get all brands
            $allProduct = Product::where('brand_id', $brandId);
        }

        // Apply search if it's provided
        if ($request->has('search')) {
            $allProduct->where('product_name', 'like', '%' . $request->search . '%');
        }

        // Paginate the results (12 per page)
        $allProduct = $allProduct->paginate(12);

        // Get the authenticated user
        $user = auth('api')->user();

        // Check if the user has a favorite for the product
        if ($user) {
            foreach ($allProduct as $product) {
                $product->is_favorite = Favorite::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists();
            }
        } else {
            foreach ($allProduct as $product) {
                $product->is_favorite = false; // Default to false if user is not authenticated
            }
        }

        // Return the results with pagination
        return $this->success($allProduct, 'Products retrieved successfully.', 200);
    }

    public function deleteProduct($id)
    {
        // Find the product by ID
        $product = Product::find($id);

        if (!$product) {
            return $this->success([], 'Product not found.', 200);
        }

        // Get the authenticated user
        $user = auth('api')->user();

        // Check if the product belongs to the authenticated user
        if ($product->user_id != $user->id) {
            return $this->error('Unauthorized: This product does not belong to you.', 403);
        }

        // Delete the product
        $product->delete();

        return $this->success([], 'Product deleted successfully.', 200);
    }

    public function getMyAllProduct()
    {
        $allProduct = Product::where('user_id', auth('api')->user()->id)->get();

        if ($allProduct->isEmpty()) {
            return $this->success([], 'No products found for this user.', 200);
        }


        return $this->success($allProduct, 'Products retrieved successfully.', 200);
    }

    public function getMyProductDetails($id)
    {
        // Get the authenticated user
        $user = auth('api')->user();



        $product = Product::where('user_id', $user->id)->with(['sizes', 'images', 'category'])->find($id);

        if (!$product) {
            return $this->error([], 'Product not found.', 403);
        }

        // Then verify the product belongs to the authenticated user
        if ($product->user_id != $user->id) {
            return $this->error('Unauthorized: This product does not belong to you.', 403);
        }


        // Check if the user has a favorite for the product
        if ($user) {
            $product->is_favorite = Favorite::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();
        } else {
            $product->is_favorite = false; // Default to false if user is not authenticated
        }

        return $this->success($product, 'Product details retrieved successfully.', 200);
    }

    public function getSuggestedProducts()
    {
        // Get the authenticated user
        $user = auth('api')->user();

        if ($user) {
            $suggestedProducts = Product::where('user_id', '!=', $user->id)->with(['sizes', 'images', 'category'])->InRandomOrder()->take(10)->get();
        } else {
            $suggestedProducts = Product::with(['sizes', 'images', 'category'])->InRandomOrder()->take(10)->get();
        }

        // Check if the user has a favorite for the product
        if ($user) {
            foreach ($suggestedProducts as $product) {
                $product->is_favorite = Favorite::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists();
            }
        } else {
            foreach ($suggestedProducts as $product) {
                $product->is_favorite = false; // Default to false if user is not authenticated
            }
        }

        if ($suggestedProducts->isEmpty()) {
            return $this->success([], 'No suggested products found.', 200);
        }



        return $this->success($suggestedProducts, 'Suggested products retrieved successfully.', 200);
    }

    public function getOrderHistory(Request $request)
    {
        $orders = Order::where('user_id', auth('api')->id())
            ->latest()
            ->paginate(12);

        $orders->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'first_name' => $order->first_name,
                'last_name' => $order->last_name,
                'address' => $order->address,
                'country' => $order->country,
                'region' => $order->region,
                'city' => $order->city,
                'zip_code' => $order->zip_code,
                'email' => $order->email,
                'total' => $order->total,
                'status' => $order->status,
                'date' => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        return $this->success($orders, 'Order history retrieved successfully.', 200);
    }
    public function getOrderHistoryDetails($orderId)
    {
        $order = Order::with(['orderItems.product'])
            ->where('user_id', auth('api')->id())
            ->findOrFail($orderId);

        $data = [
            'order_id' => $order->id,
            'total' => $order->total,
            'status' => $order->status,
            'product_count' => $order->orderItems->count(),
            'created_at' => Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
            'products' => $order->orderItems->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name ?? '',
                    'cover_photo' => url($item->product->cover_photo ?? ''),
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            }),
        ];

        return $this->success($data, 'Order details retrieved successfully.', 200);
    }

    /**
     * Get total sales and product quantity for the authenticated user.
     */
    public function getTotalSales()
    {
        $userId = Auth::id();

        $orderItemsQuery = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $userId)
            ->where('orders.status', 'completed'); // Only consider completed orders

        $totalSales = $orderItemsQuery->clone()
            ->sum(DB::raw('order_items.price * order_items.quantity'));

        $totalProducts = $orderItemsQuery->clone()
            ->sum('order_items.quantity');

        return response()->json([
            'status' => true,
            'message' => 'User order stats fetched successfully.',
            'code' => 200,
            'data' => [
                'total_sales' => round($totalSales, 2),
                'total_products' => $totalProducts,
            ],
        ]);
    }


    public function getSalesHistory(Request $request)
{
    $days = $request->input('days', 7); // Default to 7 days if not provided
    $userId = Auth::id();

    // Generate an array of dates for the last 'x' days
    $dates = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $dates[] = now()->subDays($i)->format('Y-m-d');
    }

    // Get sales data for the generated date range
    $salesData = DB::table('order_items')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.user_id', $userId)
        ->where('orders.status', 'completed') // Only consider completed orders
        ->whereIn(DB::raw('DATE(orders.created_at)'), $dates) // Filter only for the dates within the range
        ->select(
            DB::raw("DATE(orders.created_at) as date"),
            DB::raw('SUM(order_items.price * order_items.quantity) as total_sales')
        )
        ->groupBy(DB::raw("DATE(orders.created_at)"))
        ->get();

    // Format the response to ensure all dates are included
    $result = [];
    foreach ($dates as $date) {
        $sales = $salesData->firstWhere('date', $date);
        $result[] = [
            'date' => \Carbon\Carbon::parse($date)->format('d M'), // Format the date as "5 May"
            'total_sales' => $sales ? $sales->total_sales : 0,
        ];
    }

    return response()->json([
        'status' => true,
        'message' => "Sales data for last $days days.",
        'code' => 200,
        'data' => $result
    ]);
}

}
