<?php

namespace App\Http\Controllers\Api\Backend;

use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\User;
use App\Models\MusicCart;
use App\Models\MusicOrder;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\MusicOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class MusicCheckoutController extends Controller
{
    use ApiResponse;

    public function checkout(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            DB::beginTransaction();

            $cart = MusicCart::with('musicCartItems.music')->where('user_id', $user->id)->first();
            if (!$cart || $cart->musicCartItems->isEmpty()) {
                return response()->json(['message' => 'Your cart is empty'], 400);
            }

            $fixedPrice = 1.00;
            $musicHypeFee = 0.10;
            $total = 0;
            $lineItems = [];

            foreach ($cart->musicCartItems as $item) {
                $quantity = $item->quantity;
                $total += $fixedPrice * $quantity;

                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $item->music->music_title ?? 'Music Track',
                            'metadata' => ['music_id' => $item->music_id]
                        ],
                        'unit_amount' => (int)($fixedPrice * 100),
                    ],
                    'quantity' => $quantity,
                ];
            }

            // Add flat Music Hype Fee
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Music Hype Fee'],
                    'unit_amount' => (int)($musicHypeFee * 100),
                ],
                'quantity' => 1,
            ];

            $grandTotal = $total + $musicHypeFee;

            $order = MusicOrder::create([
                'user_id' => $user->id,
                'total' => $grandTotal,
                'status' => 'pending',
            ]);

            foreach ($cart->musicCartItems as $item) {
                MusicOrderItem::create([
                    'music_order_id' => $order->id,
                    'music_id' => $item->music_id,
                    'quantity' => $item->quantity,
                    'price' => $fixedPrice,
                ]);
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'payment_intent_data' => [
                    'transfer_group' => 'ORDER_' . $order->id,  // Set transfer group for easy tracking
                ],
                'success_url' => "https://the-hype.netlify.app/hype-music/payment-success",
                'cancel_url' => "https://the-hype.netlify.app/hype-music/payment-cancel",
                'metadata' => [
                    'music_order_id' => $order->id,
                    'user_id' => $user->id,
                ],
            ]);

            $order->update([
                'checkout_url' => $session->url,
                'checkout_session_id' => $session->id,
            ]);

            DB::commit();

            return $this->success([
                'order_id' => $order->id,
                'checkout_url' => $session->url,
            ], 'Checkout session created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stripe Checkout failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create Stripe session'], 500);
        }
    }

    public function musicWebhook(Request $request)
    {
        $endpointSecret = env('MUSIC_STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleMusicCheckoutCompleted($event->data->object);
                    break;

                case 'checkout.session.expired':
                    $this->handleMusicCheckoutExpired($event->data->object);
                    break;

                default:
                    Log::info('Unhandled event type: ' . $event->type);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Music webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 400);
        }
    }

    protected function handleMusicCheckoutCompleted($session)
    {
        $orderId = $session->metadata->music_order_id;
        $userId = $session->metadata->user_id;

        // Step 1: Mark order as completed
        $order = \App\Models\MusicOrder::find($orderId);
        if ($order) {
            $order->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        }

        // Step 2: Clear the music cart
        $cart = \App\Models\MusicCart::with('musicCartItems')->where('user_id', $userId)->first();
        if ($cart) {
            $cart->musicCartItems()->delete();
        }

        // Step 3: Handle payouts to music sellers (optional)
        $orderItems = MusicOrderItem::with('music.user')->where('music_order_id', $orderId)->get();

        // Group by seller and calculate payouts
        $sellers = $orderItems->groupBy(fn($item) => $item->music->user->id ?? null);

        foreach ($sellers as $sellerId => $items) {
            if (!$sellerId) continue;

            $seller = User::find($sellerId);

            if (!$seller || !$seller->stripe_account_id) continue;

            $total = $items->sum(fn($item) => $item->quantity * $item->price);
            $serviceFee = $total * 0.10;
            $finalAmount = $total - $serviceFee;

            try {
                \Stripe\Transfer::create([
                    'amount' => (int)($finalAmount * 100), // in cents
                    'currency' => 'usd',
                    'destination' => $seller->stripe_account_id,
                    'transfer_group' => 'MUSIC_ORDER_' . $orderId,
                ]);

                Log::info("Transferred \${$finalAmount} to seller #{$sellerId} for music order #{$orderId}");
            } catch (\Exception $e) {
                Log::error("Transfer error to seller #{$sellerId} for music order #{$orderId}: " . $e->getMessage());
            }
        }
    }

    protected function handleMusicCheckoutExpired($session)
    {
        $orderId = $session->metadata->music_order_id ?? null;

        if ($orderId) {
            MusicOrder::where('id', $orderId)->update([
                'status' => 'cancelled',
            ]);
        }
    }

    public function getOrderHistory()
    {
        // $user = auth('api')->user();

        // $orders = MusicOrder::with(['items.music'])
        //     ->where('user_id', $user->id)
        //     ->where('status', 'completed')
        //     ->latest()
        //     ->get()
        //     ->flatMap(function ($order) {
        //         return $order->items->map(function ($item) use ($order) {
        //             return [
        //                 'order_id' => $order->id,
        //                 'music_name' => $item->music->music_title ?? 'Unknown',
        //                 'date' => optional($order->paid_at)->format('d/m/Y') ?? $order->created_at->format('d/m/Y'),
        //                 'total_amount' => '$' . number_format($order->total, 2),
        //             ];
        //         });
        //     });
        // if ($orders->isEmpty()) {
        //     return $this->error([], 'No completed orders found', 404);
        // }

        $user = auth('api')->user();

        $orderItems = MusicOrderItem::with(['music', 'order'])
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 'completed');
            })
            ->latest()
            ->paginate(12);

        $orderItems->getCollection()->transform(function ($item) {
            return [
                'order_id'     => $item->order_id,
                'music_name'   => $item->music->music_title ?? 'Unknown',
                'date'         => optional($item->order->paid_at)->format('d/m/Y') ?? $item->order->created_at->format('d/m/Y'),
                'total_amount' => '$' . number_format($item->order->total, 2),
            ];
        });


        return $this->success($orderItems, 'Order history retrieved successfully', 200);
    }
}
