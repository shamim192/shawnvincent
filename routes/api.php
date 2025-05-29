<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Backend\MusicController;
use App\Http\Controllers\Api\Backend\ReviewController;
use App\Http\Controllers\Api\Backend\SearchController;
use App\Http\Controllers\Api\Backend\StripeController;
use App\Http\Controllers\Api\Backend\ContactController;
use App\Http\Controllers\Api\Backend\ProductController;
use App\Http\Controllers\Api\Auth\UserProfileController;
use App\Http\Controllers\Api\Backend\FavoriteController;
use App\Http\Controllers\Api\Backend\MusicCartController;
use App\Http\Controllers\Api\Backend\AdvertisementController;
use App\Http\Controllers\Api\Backend\MusicCheckoutController;
use App\Http\Controllers\Api\Backend\MerchandiseCartController;
use App\Http\Controllers\Api\Backend\MusicAlbumCheckoutController;
use App\Http\Controllers\Api\Frontend\MusicPageController;
use App\Http\Controllers\Api\Frontend\ShopPageController;

Route::middleware('auth:api')->group(function () {
    Route::post('update/user-profile', [UserProfileController::class, 'updateProfile']);
    Route::post('update/user-password', [UserProfileController::class, 'updatePassword']);
    Route::post('update/user-avatar', [UserProfileController::class, 'updateAvatar']);

    Route::get('user/profile', [UserProfileController::class, 'getProfile']);

    // stripe connect routes
    Route::post('connect/account', [StripeController::class, 'onboard']);
    Route::get('/stripe/onboarding/complete', [StripeController::class, 'completeOnboarding'])->name('stripe.onboarding.complete');

    Route::prefix('merchandise/checkout')->group(function () {
        Route::post('/', [StripeController::class, 'paymentCheckout']);
        Route::get('/success', [StripeController::class, 'checkoutSuccess'])->name('checkout.success');
        Route::get('/cancel', [StripeController::class, 'checkoutCancel'])->name('checkout.cancel');
    });
});

Route::post('/contact', [ContactController::class, 'store']);

// Music user routes with a prefix 'music' and route names starting with 'music.'
Route::prefix('music')->name('music.')->group(function () {
    Route::post('register', [AuthController::class, 'registerMusicUser'])->name('register');
    Route::post('verify-otp', [AuthController::class, 'verifyOtpMusic'])->name('verify-otp');
    Route::post('resend-otp', [AuthController::class, 'resendOtpMusic'])->name('resend-otp');
    Route::post('login', [AuthController::class, 'loginMusicUser'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'requestPasswordResetMusic']);
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyPasswordResetOtpMusic']);
    Route::post('/set-new-password', [AuthController::class, 'setNewPasswordMusic']);
    Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtpMusic']);
    Route::get('/add', [AdvertisementController::class, 'MusicAdd'])->name('add');
    // Route for genre list
    Route::get('genre/list', [MusicController::class, 'getGenreList'])->name('genre.list');

    Route::get('new-music/list', [MusicController::class, 'getNewMusic'])->name('new-music.list');
    Route::get('all-music/list/global', [MusicController::class, 'getAllGlobalMusic'])->name('all-music.list');
    Route::get('all-album/list', [MusicController::class, 'getAllGlobalAlbum'])->name('all-album.list');
    Route::get('you-may-like/list', [MusicController::class, 'getYouMayLikeMusic'])->name('you-may-like.list');

    // Route for searching and filtering music

    Route::get('/search-filter', [SearchController::class, 'searchAndFilter']);
    Route::get('/search', [SearchController::class, 'searchMusic'])->name('search');

     // Route for fetching music details
     Route::get('{id}/details', [MusicController::class, 'getMusicDetails'])->name('details');

      // Route for fetching album details
    Route::get('album/{id}/details', [MusicController::class, 'getAlbumDetails'])->name('album.details');

    Route::get('top-artist/list', [MusicController::class, 'getTopArtist'])->name('top-artist.list');

    Route::get('download/{id}', [MusicController::class, 'downloadMusic'])->name('download');
});

// Merchandise user routes with a prefix 'merchandise' and route names starting with 'merchandise.'
Route::prefix('merchandise')->name('merchandise.')->group(function () {
    Route::post('register', [AuthController::class, 'registerMerchandiseUser'])->name('register');
    Route::post('verify-otp', [AuthController::class, 'verifyOtpMerchandise'])->name('verify-otp');
    Route::post('resend-otp', [AuthController::class, 'resendOtpMerchandise'])->name('resend-otp');
    Route::post('login', [AuthController::class, 'loginMerchandiseUser'])->name('login');
    Route::post('/forgot-password', [AuthController::class, 'requestPasswordResetMerchandise']);
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyPasswordResetOtpMerchandise']);
    Route::post('/set-new-password', [AuthController::class, 'setNewPasswordMerchandise']);
    Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtpMerchandise']);


    Route::get('/add', [AdvertisementController::class, 'MerchandiseAdd'])->name('add');
    Route::get('side-bar', [AdvertisementController::class, 'SidebarAdd'])->name('side-bar');

    // Route for fetching brand list
    Route::get('brand/list', [ProductController::class, 'getBrandList'])->name('brand.list');
    Route::get('top-brand/list', [ProductController::class, 'getTopBrandList'])->name('top-brand.list');
    Route::get('all-brand/list', [ProductController::class, 'getAllBrandList'])->name('all-brand.list');

    // Route for fetching brand details with products
    Route::get('brand-product/{id}/details', [ProductController::class, 'getBrandProductDetails'])->name('brand.details');
    // Route for fetching all products
    Route::get('product/list/{brandId}', [ProductController::class, 'getAllProduct'])->name('product.list');

    // Route for fetching product details
    Route::get('product/{id}/details', [ProductController::class, 'getProductDetails'])->name('product.details');

    // Route for fetching all sizes
    Route::get('size/list', [ProductController::class, 'getSizeList'])->name('size.list');
    // Route for fetching all categories
    Route::get('category/list', [ProductController::class, 'getCategoryList'])->name('category.list');

    // Route for searching and filtering products
    Route::get('/search-filter', [SearchController::class, 'searchAndFilterProduct']);
    Route::get('/search', [SearchController::class, 'searchProduct'])->name('search');


    // Route for reviewing a product
    Route::post('add/{productId}/review', [ReviewController::class, 'addReview'])->name('product.review');
    // Route for fetching reviews of a product
    Route::get('product/{productId}/reviews', [ReviewController::class, 'getProductReviews'])->name('product.reviews');

    // Route for suggesting products
    Route::get('suggested/products', [ProductController::class, 'getSuggestedProducts'])->name('suggested.products');

});


Route::prefix('music')->middleware(['auth:api', 'role:artist'])->group(function () {
    // Music upload route only accessible by Artists and Fans
    Route::post('upload', [MusicController::class, 'uploadMusic'])->name('upload');

    Route::post('{id}/update', [MusicController::class, 'updateMusic'])->name('update');

    // Route for adding an album
    Route::post('add-album', [MusicController::class, 'addAlbum'])->name('add-album');

    Route::post('{id}/update-album', [MusicController::class, 'updateAlbum'])->name('update-album');

    // Route for adding music to an album
    Route::post('album/{id}/add-music', [MusicController::class, 'addMusicToAlbum'])->name('album.add-music');

    // Route for fetching all music
    Route::get('album/{albumId}/music/list', [MusicController::class, 'getAlbumMusic'])->name('album.music');

    // Route for deleting a music track
    Route::delete('/{albumId}/delete/{musicId}', [MusicController::class, 'deleteAlbumMusic'])->name('delete');

    // Route for deleting a music

    Route::delete('/{id}/delete', [MusicController::class, 'deleteMusic'])->name('music.delete');

    // Route for fetching album list
    Route::get('album/list', [MusicController::class, 'getAlbumList'])->name('album.list');

    // Route for fetching all music
    Route::get('list', [MusicController::class, 'getAllMusic'])->name('list');

    Route::get('my/details/{id}', [MusicController::class, 'getMyMusicDetails'])->name('my.music.details');
    Route::get('my/list', [MusicController::class, 'getMyAllMusic'])->name('my.music.list');

    Route::get('my/album/details/{id}', [MusicController::class, 'getMyAlbumDetails'])->name('my.album.details');
});


Route::prefix('music')->middleware(['auth:api'])->group(function () {

    Route::post('/checkout', [MusicCheckoutController::class, 'checkout']);

    Route::post('/album/checkout/{albumId}', [MusicAlbumCheckoutController::class, 'checkoutAlbum']);

    Route::get('/order-history', [MusicCheckoutController::class, 'getOrderHistory']);
});

Route::prefix('merchandise')->middleware(['auth:api'])->group(function () {

    Route::post('/toggle-favorite/{productId}', [FavoriteController::class, 'toggleFavorite']);
    Route::get('/favorites', [FavoriteController::class, 'getFavorites']);

    Route::get('/order-history', [ProductController::class, 'getOrderHistory']);
    Route::get('/order-history/{orderId}/details', [ProductController::class, 'getOrderHistoryDetails']);
});


Route::prefix('music/cart')->middleware(['auth:api'])->group(function () {
    // Music upload route only accessible by Artists and Fans
    // Music cart item routes
    Route::post('/{musicId}/add', [MusicCartController::class, 'addToCart']);

    Route::get('/list', [MusicCartController::class, 'getCart']);

    Route::delete('/{musicId}/remove', [MusicCartController::class, 'removeFromCart']);
});


Route::prefix('merchandise')->name('merchandise.')->middleware(['auth:api', 'role:seller'])->group(function () {
    // Route for adding a product
    Route::post('add-product', [ProductController::class, 'addProduct'])->name('add-product');
    Route::post('/product/{id}', [ProductController::class, 'updateProduct']);
    // Route for Brand update
    Route::post('brand-update/{id}', [ProductController::class, 'updateBrand'])->name('update');

    // Route for fetching all products
    Route::get('my/product/list', [ProductController::class, 'getMyAllProduct'])->name('product.list');
    // Route for product details
    Route::get('my/product/{id}/details', [ProductController::class, 'getMyProductDetails'])->name('product.details');
    // Route for deleting a product
    Route::delete('my/product/{id}/delete', [ProductController::class, 'deleteProduct'])->name('delete-product');

    Route::get('total/sales', [ProductController::class, 'getTotalSales'])->name('total.sales');
    Route::get('sales/history', [ProductController::class, 'getSalesHistory'])->name('sales.history');
});

Route::prefix('merchandise/cart')->middleware(['auth:api'])->group(function () {
    // Music upload route only accessible by Artists and Fans
    // Music cart item routes
    Route::post('/{productId}/add', [MerchandiseCartController::class, 'addToCart']);

    Route::get('/list', [MerchandiseCartController::class, 'getCart']);

    Route::post('/update-cart/{productId}', [MerchandiseCartController::class, 'updateCartQuantity']);

    Route::delete('/{productId}/remove', [MerchandiseCartController::class, 'removeFromCart']);
});


Route::prefix('cms')->name('cms.')->group(function () {
    Route::get('music', [MusicPageController::class, 'index']);
    Route::get('shop', [ShopPageController::class, 'index']);
});


Route::group(['middleware' => 'auth:api'], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/update-avatar', [UserController::class, 'updateAvatar']);
    Route::delete('/delete-profile', [UserController::class, 'deleteProfile']);
});
