<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Checkout') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h1 class="text-3xl font-bold mb-6">Checkout</h1>

                <form method="POST" action="{{ route('checkout.process') }}">
                    @csrf
                    <!-- Cart Details -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-4">Your Cart</h3>
                        <ul class="space-y-4">
                            @foreach($cartItems as $item)
                                <li class="flex justify-between items-center">
                                    <span class="text-lg">{{ $item['product_name'] }} x {{ $item['quantity'] }}</span>
                                    <span class="text-lg font-semibold">${{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Total Amount -->
                    <div class="flex justify-between text-xl font-semibold mb-6">
                        <span>Total Amount</span>
                        <span>${{ number_format($totalAmount, 2) }}</span>
                    </div>

                    <!-- Shipping Address -->
                    <div class="mb-4">
                        <label for="address" class="block text-lg font-medium mb-2">Shipping Address</label>
                        <input type="text" name="address" id="address" value="{{ old('address') }}" required class="w-full px-4 py-2 border rounded-md">
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-6">
                        <label for="payment_method" class="block text-lg font-medium mb-2">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="w-full px-4 py-2 border rounded-md">
                            <option value="stripe">Stripe</option>
                            <option value="paypal">PayPal</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-black font-semibold py-3 rounded-md hover:bg-blue-700 transition duration-300">
                        Proceed to Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
