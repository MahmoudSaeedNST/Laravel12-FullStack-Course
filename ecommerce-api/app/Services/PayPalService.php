<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{

    // paypal fields
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //initialize the PayPal service configuration
        $this->baseUrl = config('services.paypal.mode') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->accessToken = $this->getAccessToken();
    }

    /**
     * Get the access token from PayPal.
     */
    private function getAccessToken(): ?string
    {
        // return cached access token if available
        if ($this->accessToken) {
            return $this->accessToken;
        }
        // make a request to get the access token
        try {
            // make OAuth request to PayPal
            // http class instead Curl 
            // https://developer.paypal.com/api/rest/#access-tokens
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            // check if the response is successful
            if (!$response->successful()) {
                // log the error
                Log::error('PayPal access token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to get PayPal access token: ' . $response->body());
            }
            // get the access token from the response
            $data = $response->json();
            $this->accessToken = $data['access_token'];
            return $this->accessToken;
        } catch (Exception $e) {

            // log the error
            Log::error('PayPal access token request failed', [
                'message' => $e->getMessage(),
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('PayPal authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a PayPal order
     * 
     * @param float $amount Order total amount
     * @param string $currency Currency code (default: USD)
     * @param array $metadata Additional order metadata
     * @return array PayPal order response
     * @throws Exception
     */

    public function createOrder(float $amount, string $currency = 'USD', array $metadata = []): array
    {
        try {
            // get access token
            $accessToken = $this->getAccessToken();

            // Prepare order data for PayPal API
            $orderData = [
                'intent' => 'CAPTURE',                    // Capture payment immediately
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => $currency,
                            'value' => number_format($amount, 2, '.', ''),  // Format to 2 decimal places
                        ],
                        'description' => $metadata['description'] ?? 'Order Payment',
                        'reference_id' => $metadata['order_number'] ?? uniqid('order_'),
                    ]
                ],
                'application_context' => [
                    'return_url' => $metadata['return_url'] ?? config('app.url') . '/payment/success',
                    'cancel_url' => $metadata['cancel_url'] ?? config('app.url') . '/payment/cancel',
                    'brand_name' => config('app.name'),
                    'landing_page' => 'NO_PREFERENCE',     // Let PayPal decide the best landing page
                    'user_action' => 'PAY_NOW',            // Show "Pay Now" instead of "Continue"
                ]
            ];
            // Make the API request to create the order
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders", $orderData);
            if (!$response->successful()) {
                // Log the error
                Log::error('PayPal order creation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                /*  return [
                    'success' => false,
                    'message' => 'Failed to create PayPal order.',
                    'error' => $response->json(),
                ]; */
                throw new Exception('Failed to create PayPal order: ' . $response->body());
            }
            // Return the order details
            return $response->json();
        } catch (Exception $e) {
            Log::error('PayPal create order exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Capture a PayPal order (complete the payment)
     * 
     * @param string $orderId PayPal order ID
     * @return array PayPal capture response
     * @throws Exception
     */

    public function captureOrder(string $orderId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            // Make the API request to capture the order
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

            if (!$response->successful()) {
                // Log the error
                Log::error('PayPal order capture failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to capture PayPal order: ' . $response->body());
            }

            // Return the capture details
            return $response->json();
        } catch (Exception $e) {
            Log::error('PayPal capture order exception: ' . $e->getMessage());
            throw $e;
        }
    }


    /**
     * Get details of a PayPal order
     * 
     * @param string $orderId PayPal order ID
     * @return array PayPal order details
     * @throws Exception
     */

    public function getOrderDetails(string $orderId): array
    {
        try {
            $accessToken = $this->getAccessToken();
            // Make the API request to get order details
            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

            if (!$response->successful()) {
                // Log the error
                Log::error('PayPal get order details failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('Failed to get PayPal order details: ' . $response->body());
            }

            // Return the order details
            return $response->json();
        } catch (Exception $e) {
            Log::error('PayPal get order details exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
