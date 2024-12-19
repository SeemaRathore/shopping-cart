<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased">

<header class="bg-white shadow-md py-4">
    <div class="container mx-auto flex justify-between items-center px-6">
        <div>
            <a href="{{ url('/') }}" class="text-xl font-semibold text-gray-800">Our Store</a>
        </div>

        @if (Route::has('login'))
            <nav class="flex space-x-4">
                @auth
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="text-gray-700 hover:text-gray-900">Log out</a>

                    <!-- Logout form (hidden) -->
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-gray-700 hover:text-gray-900">Register</a>
                    @endif
                @endauth
            </nav>
        @endif

    </div>
</header>

<div class="container mx-auto p-6">
    <!-- Flash Message -->
    @if (session('success'))
        <div class="bg-green-500 text-white p-4 mb-4 rounded-lg shadow-md">
            {{ session('success') }}
        </div>
    @elseif (session('error'))
        <div class="bg-red-500 text-white p-4 mb-4 rounded-lg shadow-md">
            {{ session('error') }}
        </div>
    @endif

    <!-- Cart Count -->
    <div class="flex justify-end mb-6">
        <a href="{{ route('viewCart') }}" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition duration-300">
            View Cart
            (
            @if(auth()->check())
                <!-- For logged-in users, count cart items from the database -->
                {{ auth()->user()->cart && auth()->user()->cart->items ? auth()->user()->cart->items->count() : '' }}

            @else
                <!-- For guests, count cart items from the session -->
                {{ count(session('cart', [])) }}
            @endif
            )
        </a>
    </div>


    <h1 class="text-4xl font-bold mb-6 text-center">Welcome to Our Store</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php
        $products = App\Models\Product::all(); // Fetch products
        ?>
        @foreach ($products as $product)
            <div class="border rounded-lg overflow-hidden shadow-lg bg-white">
                <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h2 class="text-lg font-semibold text-gray-800">{{ $product->name }}</h2>
                    <p class="text-gray-700 mt-2">${{ number_format($product->price, 2) }}</p>
                    <form action="{{ route('cart.add', $product->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="mt-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition duration-300">
                            Add to Cart
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>

<footer class="bg-gray-900 text-white py-6 mt-12">
    <div class="container mx-auto text-center">
        <p>&copy; 2024 Our Store. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
