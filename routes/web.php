<?php

use Stripe\Stripe;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Web\Frontend\HomeController;
use App\Http\Controllers\Api\Backend\StripeController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Web\Frontend\ContactController;
use App\Http\Controllers\Web\Frontend\SubscriberController;
use App\Http\Controllers\Api\Backend\MusicCheckoutController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Backend\MusicAlbumCheckoutController;

Route::get('/',[AuthenticatedSessionController::class, 'create'])->name('home');
Route::get('/project/show/{slug}',[HomeController::class, 'project'])->name('project.show');



//Social login test routes
Route::get('social-login/{provider}',[SocialLoginController::class,'RedirectToProvider'])->name('social.login');
Route::get('social-login/{provider}/callback',[SocialLoginController::class,'HandleProviderCallback']);


Route::post('subscriber/store',[SubscriberController::class,'store'])->name('subscriber.store');

Route::post('contact/store',[ContactController::class,'store'])->name('contact.store');


// Routes for running artisan commands
Route::get('/run-migrate-fresh', function () {
    try {
        $output = Artisan::call('migrate:fresh', ['--seed' => true]);
        return response()->json([
            'message' => 'Migrations executed.',
            'output' => nl2br($output)
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while running migrations.',
            't-error' => $e->getMessage(),
        ], 500);
    }
});

Route::post('payment/stripe-webhook', [StripeController::class, 'stripeWebhook']);

Route::post('/webhook/music', [MusicCheckoutController::class, 'musicWebhook']);

Route::post('/webhook/album', [MusicAlbumCheckoutController::class, 'handleAlbumWebhook']);

Route::get('stripe/success/{id}', [StripeController::class, 'stripeSuccess'])->name('stripe.success');
Route::get('stripe/refresh/{id}', [StripeController::class, 'stripeRefresh'])->name('stripe.refresh');

require __DIR__.'/auth.php';
