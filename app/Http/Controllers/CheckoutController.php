<?php

namespace App\Http\Controllers;


use App\Models\Product;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Order;
use App\Models\Cart;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalHttp\HttpException;


class CheckoutController extends Controller
{
    private $client;

    public function __construct()
    {
        // Set up PayPal API context
        $clientId = env('PAYPAL_CLIENT_ID');
        $clientSecret = env('PAYPAL_SECRET');
        $environment = new SandboxEnvironment($clientId, $clientSecret);
        $this->client = new PayPalHttpClient($environment);
    }

    // Show the checkout page
    public function index()
    {
        if (auth()->check()) {
            // If the user is logged in, retrieve their cart from the database
            $cart = Cart::where('user_id', auth()->id())->first();
            $cartItems = $cart ? $cart->items : [];

            // Check if there's a guest cart saved in the session
            $guestCartItems = session()->get('cart', []);

            // If the user has a guest cart, merge the items into the user's cart
            if ($guestCartItems) {
                foreach ($guestCartItems as $guestItem) {
                    // Add the guest items to the logged-in user's cart
                    $cart->items()->create([
                        'product_id' => $guestItem['id'],
                        'quantity' => $guestItem['quantity'],
                        'price' => $guestItem['price'],
                    ]);
                }

                // Clear the guest cart from session after merging
                session()->forget('cart');
            }
        } else {
            // If the user is not logged in, retrieve the guest cart from the session
            $cartItems = session()->get('cart', []);
        }

        if (is_null($cartItems)) {
            $cartItems = [];
        }

        // Calculate the total amount of the cart
        $totalAmount = 0;
        foreach ($cartItems as $item) {
            // Assuming product name is added to the item for simplicity
            $product = Product::find($item['product_id']);
            // Find the product from the DB
            $item['product_name'] = $product->name; // Add the product name to the item
            $totalAmount += $item['price'] * $item['quantity'];
        }

        return view('checkout.index', [
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
        ]);
    }


    // Handle the checkout process and payment
    public function processCheckout(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'payment_method' => 'required|string',
        ]);

        // Fetch cart items based on whether user is authenticated
        if (auth()->check()) {
            $cart = Cart::where('user_id', auth()->id())->first();
            $cartItems = $cart ? $cart->items : [];
        } else {
            $cartItems = session()->get('cart', []);
        }

        $totalAmount = 0;
        $itemTotal = 0; // Initialize item total

        foreach ($cartItems as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
            $itemTotal += $item['price'] * $item['quantity']; // Add item total for each item
        }

        // Handling Stripe Payment
        if ($request->payment_method == 'stripe') {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            try {
                // Creating the payment intent for stripe
                $paymentIntent = PaymentIntent::create([
                    'amount' => $totalAmount * 100, // Convert to cents
                    'currency' => 'usd',
                    'payment_method_types' => ['card'], // Only card payment method
                ]);

                $order = new Order();
                $order->user_id = auth()->id();
                $order->total_amount = $totalAmount;
                $order->payment_method = 'stripe';
                $order->shipping_address = $request->address;
                $order->status = 'pending';

                // Store items as JSON
                $orderItems = [];
                foreach ($cartItems as $item) {
                    $orderItems[] = [
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ];
                }
                $order->items = json_encode($orderItems); // Save items as JSON
                $order->save();

                session()->forget('cart');
                return redirect()->route('order.complete', $order->id);
            } catch (\Exception $e) {
                dd($e->getMessage());
                return back()->withErrors('Error: ' . $e->getMessage());
            }
        }

        // Handling PayPal Payment
        elseif ($request->payment_method == 'paypal') {
            // Prepare PayPal order data with item_total
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => $totalAmount,
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format($itemTotal, 2, '.', '')
                                ]
                            ]
                        ],
                        'items' => []
                    ]
                ],
                'shipping' => [
                    'address' => [
                        'address_line_1' => $request->address,  // Ensure the address is passed
                        'address_line_2' => '',
                        'admin_area_2' => 'City', // Replace with dynamic city if necessary
                        'admin_area_1' => 'State', // Replace with dynamic state if necessary
                        'postal_code' => '12345', // Replace with dynamic postal code if necessary
                        'country_code' => 'US', // You can dynamically set the country code as needed
                    ]
                ]
            ];

            // Add items to PayPal order data
            foreach ($cartItems as $item) {
                $product = \App\Models\Product::find($item['id']);

                // Ensure product exists before using its name
                if ($product) {
                    $orderData['purchase_units'][0]['items'][] = [
                        'name' => $product->name, // Fetch product name dynamically
                        'quantity' => $item['quantity'],
                        'unit_amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($item['price'], 2, '.', ''),
                        ]
                    ];
                }
            }

            $request = new OrdersCreateRequest();
            $request->body = json_encode($orderData);

            try {
                // Execute PayPal request
                $response = $this->client->execute($request);

                // Get the approval link from PayPal response
                $approvalUrl = $response->result->links[1]->href;

                // Redirect user to PayPal for payment approval
                return redirect()->away($approvalUrl);
            } catch (HttpException $e) {
                dd($e);
                return back()->withErrors('Error: ' . $e->getMessage());
            }
        }
    }

    // Handle PayPal payment success
    public function success(Request $request)
    {
        $paymentId = $request->paymentId;
        $payerId = $request->PayerID;

        $request = new OrdersCaptureRequest($paymentId);

        try {
            $response = $this->client->execute($request);

            $order = new Order();
            $order->user_id = auth()->id();
            $order->total_amount = $response->result->purchase_units[0]->amount->value;
            $order->payment_method = 'paypal';
            $order->shipping_address = request('address');
            $order->status = 'paid';  // Change to 'paid' after successful payment
            dd($order);
            // Store items as JSON
            $orderItems = [];
            foreach ($response->result->purchase_units[0]->items as $item) {
                $orderItems[] = [
                    'product_id' => $item->sku,
                    'quantity' => $item->quantity,
                    'price' => $item->unit_amount->value,
                ];
            }
            $order->items = $orderItems;
            $order->save();

            // Clear the cart
            session()->forget('cart');

            return redirect()->route('order.complete', $order->id);
        } catch (\Exception $e) {
            return back()->withErrors('Error: ' . $e->getMessage());
        }
    }

    // Handle PayPal payment cancellation
    public function cancel()
    {
        return redirect()->route('checkout.index')->with('error', 'Payment was cancelled');
    }

    // Display order completion details
    public function orderComplete($orderId)
    {
        $order = Order::findOrFail($orderId);
        return view('checkout.complete', compact('order'));
    }
}
