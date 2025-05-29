<?php

namespace App\Http\Controllers\Api\Backend;

use Stripe\Stripe;
use Stripe\Account;
use Stripe\Webhook;
use App\Models\User;
use App\Models\Order;
use Stripe\AccountLink;
use App\Models\OrderItem;
use Stripe\PaymentIntent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\MerchandiseCart;
use Illuminate\Support\Facades\DB;
use App\Models\MerchandiseCartItem;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class StripeController extends Controller
{
    use ApiResponse;

    public function onboard(Request $request)
    {

        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.',
            ], 401);
        }

        if ($user->is_stripe_onboarded && $user->stripe_account_id) {
            try {

                Stripe::setApiKey(config('stripe.secret_key'));


                $loginLink = \Stripe\Account::createLoginLink($user->stripe_account_id);


                return $this->success(['url' => $loginLink->url], 'Redirecting to Stripe Express Dashboard..');
            } catch (\Exception $e) {
                Log::info($e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error generating Stripe login link: ' . $e->getMessage(),
                ], 500);
            }
        }


        try {

            Stripe::setApiKey(config('stripe.secret_key'));


            $account = Account::create([
                'type' => 'express',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'daily', // Change to 'weekly' or 'monthly' if needed
                        ],
                    ],
                ],
            ]);


            $link = AccountLink::create([
                'account' => $account->id,
                'refresh_url' => route('stripe.refresh', ['id' => $account->id]),
                'return_url' => route('stripe.success', ['id' => $account->id]),
                'type' => 'account_onboarding',
            ]);

            return $this->success(['url' => $link->url], 'Onboarding link generated successfully.');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Stripe API error: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function stripeSuccess($id)
    {
        try {

            Stripe::setApiKey(config('stripe.secret_key'));


            $account = Account::retrieve($id);


            $user = User::where('email', $account->email)->first();


            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found in the database for this Stripe account.',
                ], 404);
            }


            $user->update([
                'stripe_account_id' => $id,
                'is_stripe_onboarded' => true
            ]);


            return $this->redirectToStripeDashboard($user->stripe_account_id);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error processing onboarding success: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function redirectToStripeDashboard($stripeAccountId)
    {
        try {

            Stripe::setApiKey(config('stripe.secret_key'));


            $loginLink = \Stripe\Account::createLoginLink($stripeAccountId);


            return redirect()->away($loginLink->url);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating Stripe login link: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function stripeRefresh($id)
    {
        try {
            // Set Stripe API key
            Stripe::setApiKey(config('stripe.secret_key'));

            // Find user based on Stripe account ID
            $user = User::where('stripe_account_id', $id)->first();


            // Generate a new Stripe onboarding link
            $link = AccountLink::create([
                'account' => $id,
                'refresh_url' => route('stripe.refresh', ['id' => $id]), // Refresh link
                'return_url' => route('stripe.success', ['id' => $id]),  // Success link
                'type' => 'account_onboarding',
            ]);

            // Redirect user to the new onboarding link
            return redirect()->away($link->url);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error generating refresh link: ' . $e->getMessage(),
            ], 500);
        }
    }






    public function onboardResult($encodedToken)
    {
        try {
            $user = User::where('stripe_account_id', Crypt::decrypt($encodedToken))->firstOrFail();


            $user->stripe_boarding_completed = 'completed';
            $user->save();

            return redirect(route('dashboard'));
        } catch (\Exception $e) {

            Log::error('Error processing Stripe Onboarding result', [
                'error_message' => $e->getMessage(),
                'encoded_token' => $encodedToken,
                'stack_trace' => $e->getTraceAsString(),
            ]);


            return $this->error([], 'Error processing onboarding result: ' . $e->getMessage(), 500);
        }
    }

    public function paymentCheckout(Request $request)
    {
        // Set Stripe API key
        Stripe::setApiKey(env('STRIPE_SECRET')); // Ensure your secret key is in .env file

        $user = Auth::user();

        // Ensure sellers cannot initiate a checkout session
        if ($user->role === 'seller') {
            return response()->json(['message' => 'Sellers cannot initiate a checkout session.'], 200);
        }

        try {
            DB::beginTransaction();

            // Fetch the user's cart items
            $cart = MerchandiseCart::with('merchandiseCartItems.product.user')->where('user_id', $user->id)->first();

            if (!$cart || $cart->merchandiseCartItems->isEmpty()) {
                return response()->json(['message' => 'Cart is empty.'], 200);
            }

            $groupedCart = [];
            $serviceFeeRate = 0.10; // 10% service fee (you can adjust this as needed)
            $deliveryFee = 0;

            // Group cart items by seller and calculate amounts
            foreach ($cart->merchandiseCartItems as $cartItem) {
                $seller = $cartItem->product->user;

                // Ensure seller has Stripe account
                if (!$seller || !$seller->stripe_account_id) {
                    return response()->json(['message' => "A seller is missing Stripe details. Cannot process payment."], 400);
                }

                $sellerAccountId = $seller->stripe_account_id;
                $amount = $cartItem->quantity * $cartItem->product->price;
                $serviceFee = $amount * $serviceFeeRate;
                $sellerAmount = $amount - $serviceFee;

                if (!isset($groupedCart[$sellerAccountId])) {
                    $groupedCart[$sellerAccountId] = [
                        'seller_account_id' => $sellerAccountId,
                        'amount' => 0,
                        'service_fee' => 0,
                        'seller_amount' => 0
                    ];
                }

                $groupedCart[$sellerAccountId]['amount'] += $amount;
                $groupedCart[$sellerAccountId]['service_fee'] += $serviceFee;
                $groupedCart[$sellerAccountId]['seller_amount'] += $sellerAmount;
            }

            // Calculate total amount
            $totalAmount = collect($groupedCart)->sum(function ($seller) {
                return $seller['amount'];
            }) + $deliveryFee;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'address' => $request->address,
                'country' => $request->country,
                'region' => $request->region,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'email' => $request->email,
                'order_note' => $request->order_note ?? null,
                'total' => $totalAmount,
                'paid_at' => now(),
                'status' => 'pending',  // Order starts in a pending state
            ]);

            // Store order items
            foreach ($cart->merchandiseCartItems as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->product->price,
                ]);
            }

            // Create Stripe Checkout session
            $checkoutSession = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $cart->merchandiseCartItems->map(function ($cartItem) {
                    return [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $cartItem->product->product_name,
                                'metadata' => [
                                    'product_id' => $cartItem->product_id
                                ],
                            ],
                            'unit_amount' => (int)($cartItem->product->price * 100), // Stripe expects amounts in cents
                        ],
                        'quantity' => $cartItem->quantity,
                    ];
                })->toArray(),
                'mode' => 'payment',
                'payment_intent_data' => [
                    'transfer_group' => 'ORDER_' . $order->id,  // Set transfer group for easy tracking
                ],
                'success_url' => "https://the-hype.netlify.app/hype-store/payment-success",
                'cancel_url' => "https://the-hype.netlify.app/hype-store/payment-cancel",
                'metadata' => [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ],
                'expires_at' => now()->addMinutes(30)->timestamp,
            ]);

            // Update order with the Stripe checkout URL
            $order->update(['checkout_url' => $checkoutSession->url]);

            // Commit the transaction
            DB::commit();

            // Return response with order details and checkout URL
            return $this->success([
                'order_id' => $order->id,
                'checkout_url' => $checkoutSession->url,
            ], 'Checkout session created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout session creation failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stripeWebhook(Request $request)
    {
        $endpointSecret = env('STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);

            // Handle different event types
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionCompleted($session);
                    break;
                case 'checkout.session.expired':
                    $session = $event->data->object;
                    $this->handleCheckoutSessionExpired($session);
                    break;
                default:
                    Log::info('Unhandled event type: ' . $event->type);
            }

            // Return success response to Stripe
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            // Log the error if the webhook signature is invalid or any other error occurs
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 400);
        }
    }

    protected function handleCheckoutSessionCompleted($session)
    {
        $orderId = $session->metadata->order_id;
        $userId = $session->metadata->user_id;

        // Find the order by ID and update its status to completed
        $order = Order::find($orderId);
        if ($order) {
            $order->update(['status' => 'completed']);
        }

        // Fetch the cart and delete all cart items
        $cart = MerchandiseCart::with('merchandiseCartItems')->where('user_id', $userId)->first();
        if ($cart) {
            $cart->merchandiseCartItems()->delete();
        }

        // Notify the user that their order is created
        $user = User::find($userId);
        // Handle seller payouts
        $sellerIds = OrderItem::where('order_id', $orderId)
            ->with('product.user')
            ->get()
            ->pluck('product.user.id')
            ->unique();

        foreach ($sellerIds as $sellerId) {
            $seller = User::find($sellerId);
            if ($seller && $seller->stripe_account_id) {
                $sellerAccountId = $seller->stripe_account_id;

                // Calculate the total amount for the seller
                $sellerAmount = OrderItem::where('order_id', $orderId)
                    ->whereHas('product', function ($query) use ($sellerId) {
                        $query->where('user_id', $sellerId);
                    })
                    ->sum(DB::raw('quantity * price'));

                // Deduct the service fee (10%)
                $serviceFee = $sellerAmount * 0.10;
                $finalSellerAmount = $sellerAmount - $serviceFee;

                try {
                    // Transfer money to the seller's Stripe account
                    \Stripe\Transfer::create([
                        'amount' => (int)($finalSellerAmount * 100), // Convert to cents
                        'currency' => 'usd',
                        'destination' => $sellerAccountId,
                        'transfer_group' => 'ORDER_' . $orderId,
                    ]);

                    Log::info("Successfully transferred amount to seller {$sellerId} for order {$orderId}");
                } catch (\Exception $e) {
                    // Log any errors that occur while transferring money to the seller
                    Log::error("Error transferring amount to seller {$sellerId} for order {$orderId}: " . $e->getMessage());
                }
            }
        }
    }

    protected function handleCheckoutSessionExpired($session)
    {
        $orderId = $session->metadata->order_id;

        // Find the order and update its status to cancelled
        $order = Order::find($orderId);
        if ($order) {
            $order->update(['status' => 'cancelled']);
        }

        Log::info("Checkout session expired for order {$orderId}");
    }
}
