<?php

namespace App\Http\Controllers\Api\Backend;

use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\User;
use App\Models\Album;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MusicAlbumCheckoutController extends Controller
{
    use ApiResponse;

    public function checkoutAlbum($albumId)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $album = Album::with('music')->find($albumId);
        if (!$album) {
            return response()->json(['message' => 'Album not found'], 404);
        }

        // Prevent duplicate "active" purchase
        if ($user->purchasedAlbums()
            ->wherePivot('album_id', $albumId)
            ->wherePivot('status', 'completed')
            ->exists()
        ) {
            return response()->json(['message' => 'You already purchased this album.'], 200);
        }

        try {
            $price = 2.00;

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $album->album_name,
                            'description' => 'By ' . $album->artist_name,
                        ],
                        'unit_amount' => (int)($price * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'payment_intent_data' => [
                    'transfer_group' => 'ALBUM_' . $album->id,
                ],
                'success_url' => "https://the-hype.netlify.app/hype-music/payment-success",
                'cancel_url' => "https://the-hype.netlify.app/hype-music/payment-cancel",
                'metadata' => [
                    'album_id' => $album->id,
                    'user_id' => $user->id,
                ],
            ]);

            // Record pending purchase in pivot table
            $user->purchasedAlbums()->syncWithoutDetaching([
                $album->id => [
                    'status' => 'pending',
                    'checkout_session_id' => $session->id,
                    'checkout_url' => $session->url,
                    'purchased_at' => null,
                ]
            ]);


            return $this->success([
                'checkout_url' => $session->url,
            ], 'Checkout session created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Stripe Album Checkout failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create Stripe session'], 500);
        }
    }

    public function handleAlbumWebhook(Request $request)
    {
        $endpointSecret = env('ALBUM_STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleAlbumCheckoutCompleted($event->data->object);
                    break;

                case 'checkout.session.expired':
                    $this->handleAlbumCheckoutExpired($event->data->object);
                    break;

                default:
                    Log::info('Unhandled album webhook event: ' . $event->type);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Album webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook verification failed'], 400);
        }
    }

    protected function handleAlbumCheckoutCompleted($session)
    {
        $albumId = $session->metadata->album_id ?? null;
        $userId = $session->metadata->user_id ?? null;

        if (!$albumId || !$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        // Update pivot record from 'pending' to 'completed'
        $user->purchasedAlbums()->updateExistingPivot($albumId, [
            'status' => 'completed',
            'purchased_at' => now(),
        ]);

        Log::info("Album #{$albumId} marked as completed for user #{$userId}");

        // Optional payout to seller
        $album = Album::with('music.user')->find($albumId);
        if ($album && $album->music->count() > 0) {
            $seller = $album->music->first()->user;

            if ($seller && $seller->stripe_account_id) {
                $serviceFee = 0.10 * 2.00;
                $finalAmount = 2.00 - $serviceFee;

                try {
                    \Stripe\Transfer::create([
                        'amount' => (int)($finalAmount * 100),
                        'currency' => 'usd',
                        'destination' => $seller->stripe_account_id,
                        'transfer_group' => 'ALBUM_' . $albumId,
                    ]);

                    Log::info("Transferred \${$finalAmount} to seller #{$seller->id} for album #{$albumId}");
                } catch (\Exception $e) {
                    Log::error("Album payout failed for seller #{$seller->id}: " . $e->getMessage());
                }
            }
        }
    }

    protected function handleAlbumCheckoutExpired($session)
    {
        $albumId = $session->metadata->album_id ?? null;
        $userId = $session->metadata->user_id ?? null;

        if (!$albumId || !$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        $user->purchasedAlbums()->updateExistingPivot($albumId, [
            'status' => 'expired',
        ]);

        Log::info("Album #{$albumId} marked as expired for user #{$userId}");
    }
}
