<?php

namespace App\Http\Controllers\Api;

use App\Enum\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Stripe;

class PaymentController extends Controller
{
    //initialize the controller
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createPayment(Request $request, Order $order)
    {
        // validate the request
        // implode example: implode(',', ['stripe', 'paypal'])
        // will return 'stripe,paypal'
        $request->validate([
            'provider' => 'required|string|in:' . implode(',', PaymentProvider::values()),
        ]);

        // check if the order belongs to the exact user
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized. This order does not belong to you.'
            ], 403);
        }

        // check if order can be paid
        if (!$order->canAcceptPayment()) {
            return response()->json([
                'message' => 'This order cannot be paid.'
            ], 400);
        }

        // check correct payment provider
        $provider = PaymentProvider::from($request->input('provider'));
        if ($provider === PaymentProvider::STRIPE) {
            return $this->createStripePayment($order);
        } else {
            // For now, we'll only implement Stripe
            return response()->json([
                'message' => 'Payment provider not implemented yet.'
            ], 501);
        }
    }
}
