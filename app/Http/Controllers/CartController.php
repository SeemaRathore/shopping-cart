<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function addToCart(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // Check if the user is logged in
        if (auth()->check()) {
            // For logged-in users, save the cart in the database
            $user = auth()->user();

            // Get the user's cart or create a new one
            $cart = $user->cart()->firstOrCreate([]);

            // Check if the product already exists in the cart
            $cartItem = $cart->items()->where('product_id', $product->id)->first();

            if ($cartItem) {
                // If the product exists, update the quantity
                $cartItem->quantity += 1;
                $cartItem->save();
            } else {
                // If the product doesn't exist, add it to the cart
                $cart->items()->create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => $product->price,
                ]);
            }
        } else {
            // For guests, store cart data in session
            $cartItems = session()->get('cart', []);

            // Check if the product already exists in the cart
            $productExists = false;

            foreach ($cartItems as &$item) {
                if ($item['id'] === $product->id) {
                    // If the product exists, increase the quantity
                    $item['quantity'] += 1;
                    $productExists = true;
                    break;
                }
            }

            // If the product doesn't exist, add it to the cart with 'id'
            if (!$productExists) {
                $cartItems[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => 1,
                    'price' => $product->price,
                    'image' => $product->image,
                    'description' => $product->description,
                ];
            }

            // Store the updated cart in the session
            session(['cart' => $cartItems]);
        }

        return redirect()->back()->with('success', 'Product added to cart!');
    }

    public function viewCart()
    {
        // For authenticated users, fetch the cart from the database
        if (auth()->check()) {
            $cart = Cart::with('items.product')->where('user_id', auth()->id())->first();
            $cartItems = $cart ? $cart->items : [];
        } else {
            // For guest users, fetch the cart from the session
            $cartItems = session('cart', []);
        }

        // If no cart, return a view indicating that the cart is empty
        if (!$cartItems || (is_array($cartItems) && count($cartItems) === 0)) {
            return view('cart.index')->with('cart', []);  // Passing empty cart data
        }

        // Calculate the cart totals (using the new function)
        $cartTotalData = $this->calculateCartTotal($cartItems);

        // Pass the cart data and total calculations to the view
        return view('cart.index', [
            'cart' => $cartItems,
            'cartTotal' => $cartTotalData['cartTotal'],
            'taxAmount' => $cartTotalData['taxAmount'],
            'shippingAmount' => $cartTotalData['shippingAmount'],
            'totalWithTaxAndShipping' => $cartTotalData['totalWithTaxAndShipping']
        ]);
    }

    public function updateQuantity(Request $request, $id)
    {
        // For a logged-in user
        if (auth()->check()) {
            // Retrieve the cart for the authenticated user
            $cart = Cart::where('user_id', auth()->id())->first();  // Fetch the cart based on user_id

            if (!$cart) {
                return redirect()->route('viewCart')->with('error', 'Cart not found');
            }

            // Retrieve the cart item from the cart_items table based on cart_id
            $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $id)->first();  // Filter by cart_id and item id

            if (!$cartItem) {
                return redirect()->route('viewCart')->with('error', 'Cart item not found');
            }

            // Update the quantity of the cart item
            $cartItem->update(['quantity' => $request->quantity]);

            // Get the updated cart items for the user
            $cartItems = CartItem::where('cart_id', $cart->id)->get(); // Get cart items by cart_id

            // Recalculate cart totals after updating quantity
            $cartTotalData = $this->calculateCartTotal($cartItems);

            // Return the cart view with success message and updated totals
            return view('cart.index', [
                'cart' => $cartItems,
                'cartTotal' => $cartTotalData['cartTotal'],
                'taxAmount' => $cartTotalData['taxAmount'],
                'shippingAmount' => $cartTotalData['shippingAmount'],
                'totalWithTaxAndShipping' => $cartTotalData['totalWithTaxAndShipping'],
            ])->with('success', 'Quantity updated successfully');
        }

        // For a guest user (stored in session)
        if ($request->session()->has('cart')) {
            // Get the current cart items from the session
            $cart = $request->session()->get('cart');

            // Loop through the cart to find the item by its 'id'
            foreach ($cart as $key => $item) {
                if ($item['id'] == $id) {
                    // Update the quantity of the item in the session
                    $cart[$key]['quantity'] = $request->quantity;
                    break;
                }
            }

            // Re-index the array after updating the quantity
            $request->session()->put('cart', array_values($cart));

            // Recalculate cart totals after updating quantity
            $cartTotalData = $this->calculateCartTotal($cart);

            // Return the cart view with success message and updated totals
            return view('cart.index', [
                'cart' => $cart,
                'cartTotal' => $cartTotalData['cartTotal'],
                'taxAmount' => $cartTotalData['taxAmount'],
                'shippingAmount' => $cartTotalData['shippingAmount'],
                'totalWithTaxAndShipping' => $cartTotalData['totalWithTaxAndShipping'],
            ])->with('success', 'Quantity updated successfully');
        }

        // Fallback if no cart data exists
        return redirect()->back()->with('error', 'Cart not found');
    }


    public function removeProduct(Request $request)
    {
        // For a logged-in user
        if (auth()->check()) {
            // Retrieve the cart for the authenticated user
            $cart = Cart::where('user_id', auth()->id())->first();  // Fetch the cart based on user_id

            if (!$cart) {
                return redirect()->route('cart.index')->with('error', 'Cart not found');
            }

            // Retrieve the cart item from the cart_items table based on cart_id and item_id
            $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $request->item_id)->first();

            if (!$cartItem) {
                return redirect()->route('viewCart')->with('error', 'Cart item not found');
            }

            // Delete the cart item
            $cartItem->delete();

            // Get the updated cart items for the user
            $cartItems = CartItem::where('cart_id', $cart->id)->get(); // Get cart items by cart_id

            // Recalculate cart totals after removing the product
            $cartTotalData = $this->calculateCartTotal($cartItems);

            // Return the cart view with success message and updated totals
            return view('cart.index', [
                'cart' => $cartItems,
                'cartTotal' => $cartTotalData['cartTotal'],
                'taxAmount' => $cartTotalData['taxAmount'],
                'shippingAmount' => $cartTotalData['shippingAmount'],
                'totalWithTaxAndShipping' => $cartTotalData['totalWithTaxAndShipping'],
            ])->with('success', 'Product removed from cart');
        }

        // For a guest user (stored in session)
        if ($request->session()->has('cart')) {
            // Get the current cart items from the session
            $cart = $request->session()->get('cart');

            // Loop through the cart to find the item by its 'id'
            foreach ($cart as $key => $item) {
                if ($item['id'] == $request->item_id) {
                    // Remove the item from the cart array
                    unset($cart[$key]);
                    break;
                }
            }

            // Re-index the array after removing the item
            $request->session()->put('cart', array_values($cart));

            // Recalculate cart totals after removing the product
            $cartTotalData = $this->calculateCartTotal($cart);

            // Return the cart view with success message and updated totals
            return view('cart.index', [
                'cart' => $cart,
                'cartTotal' => $cartTotalData['cartTotal'],
                'taxAmount' => $cartTotalData['taxAmount'],
                'shippingAmount' => $cartTotalData['shippingAmount'],
                'totalWithTaxAndShipping' => $cartTotalData['totalWithTaxAndShipping'],
            ])->with('success', 'Product removed from cart');
        }

        // Fallback if no cart data exists
        return redirect()->route('viewCart')->with('error', 'Cart not found');
    }



    public function calculateCartTotal($cart)
    {
        // Initialize variables
        $cartTotal = 0;
        $taxRate = env('TAX_RATE', 0.1); // Get tax rate from .env or default to 10%
        $shippingAmount = 5.00; // Shipping cost, modify based on your needs

        // Loop through cart to calculate total price and tax
        foreach ($cart as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        // Calculate tax
        $taxAmount = $cartTotal * $taxRate;

        // Calculate total price with tax and shipping
        $totalWithTaxAndShipping = $cartTotal + $taxAmount + $shippingAmount;

        return [
            'cartTotal' => $cartTotal,
            'taxAmount' => $taxAmount,
            'shippingAmount' => $shippingAmount,
            'totalWithTaxAndShipping' => $totalWithTaxAndShipping
        ];
    }


}

