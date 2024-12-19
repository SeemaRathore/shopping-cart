<!DOCTYPE html>
<html lang="en">

<head>
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 font-sans leading-normal tracking-normal">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Display Flash Message -->
    @if (session('success'))
        <div class="bg-green-500 text-white p-4 rounded-md shadow-md mb-4">
            <p>{{ session('success') }}</p>
        </div>
    @elseif (session('error'))
        <div class="bg-red-500 text-white p-4 rounded-md shadow-md mb-4">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <h2 class="text-4xl font-bold text-gray-800 mb-8">Your Shopping Cart</h2>

    @if (empty($cart))
        <div class="text-center p-6 border-2 border-gray-300 rounded-md bg-white shadow-sm">
            <p class="text-xl text-gray-600">Your cart is empty. Start shopping!</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($cart as $item)
                <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center">
                    <!-- Product Image -->
                    <img
                        src="{{ auth()->check()
                ? asset($item['product']['image'])
                : asset($item['image']) }}"
                    alt="{{ $item['name'] }}"
                    class="w-40 h-40 object-cover rounded-md mb-4 border-2 border-gray-200">

                    <!-- Product Details -->
                    <h3 class="text-xl font-semibold text-gray-800 mb-2 text-center">{{ $item['name'] }}</h3>
                    <p class="text-lg text-gray-600">Price: ${{ number_format($item['price'], 2) }}</p>

                    <!-- Quantity Update Form -->
                    <form method="POST" action="{{ route('cart.update', $item['id']) }}" id="quantity-form-{{ $item['id'] }}" class="mt-4 w-full">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center justify-between space-x-4">
                            <label for="quantity-{{ $item['id'] }}" class="text-sm text-gray-600">Quantity</label>
                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1"
                                   class="w-16 p-2 text-center border rounded-md quantity-input" id="quantity-{{ $item['id'] }}" data-item-id="{{ $item['id'] }}" />
                        </div>
                    </form>

                    <p class="text-lg text-gray-600 mt-2">Subtotal: ${{ number_format($item['price'] * $item['quantity'], 2) }}</p>

                    <!-- Remove Button -->
                    <form method="POST" action="{{ route('cart.remove',$item['id']) }}" class="mt-4">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="item_id" value="{{ $item['id'] }}">  <!-- Pass item_id here -->
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition duration-200 w-full">
                            Remove
                        </button>
                    </form>

                </div>
            @endforeach
        </div>
    @endif

    <!-- Cart Summary Section -->
     @if(!empty($cart))
         <div class="mt-8 bg-white shadow-lg p-6 rounded-lg">
             <div class="text-right">
                 <p class="text-lg font-semibold text-gray-800">Subtotal: ${{ number_format($cartTotal, 2) }}</p>
                 <p class="text-lg text-gray-600">Tax ({{ env('TAX_RATE') * 100 }}%): ${{ number_format($taxAmount, 2) }}</p>
                 <p class="text-lg text-gray-600">Shipping: ${{ number_format($shippingAmount, 2) }}</p>
                 <p class="text-xl font-bold text-gray-900">Total with Tax & Shipping: ${{ number_format($totalWithTaxAndShipping, 2) }}</p>
             </div>
         </div>
     @endif

    <!-- Checkout Section -->
    <div class="mt-6 flex justify-between items-center">
        <a href="{{ url('/') }}" class="bg-gray-800 text-white px-6 py-3 rounded-full text-lg hover:bg-gray-700 transition duration-200">
            Back to Products
        </a>
        @if (!empty($cart) && count($cart) > 0)
            <a href="{{ route('checkout.index') }}" class="bg-blue-600 text-white px-6 py-3 rounded-full text-lg hover:bg-blue-700 transition duration-200">
                Proceed to Checkout
            </a>
        @endif
    </div>
</div>

<script>
    document.querySelectorAll('.quantity-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var form = document.getElementById('quantity-form-' + input.dataset.itemId);
            form.submit(); // Automatically submit the form when quantity is changed
        });
    });
</script>
</body>

</html>
