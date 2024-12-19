<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

<!-- Main Container -->
<div class="max-w-7xl mx-auto p-6">

    <!-- Display Cart Count -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="{{ route('cart.index') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
                View Cart ({{ count(session('cart', [])) }})
            </a>
        </div>

        <!-- Logout Button -->
        @auth
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-300">
                    Logout
                </button>
            </form>
        @endauth
    </div>

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

    <!-- Product List Title -->
    <h1 class="text-4xl font-semibold text-center text-gray-800 mb-8">Product List</h1>

    <!-- Display Products Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
        <table class="min-w-full border-collapse table-auto">
            <thead class="bg-gray-200">
            <tr>
                <th class="border p-3 text-left text-gray-700">ID</th>
                <th class="border p-2">Image</th>
                <th class="border p-3 text-left text-gray-700">Name</th>
                <th class="border p-3 text-left text-gray-700">Price</th>
                <th class="border p-3 text-center text-gray-700">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr class="border-b hover:bg-gray-50">
                    <td class="border p-3 text-gray-800">{{ $product->id }}</td>
                    <td class="border p-2">
                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover">

                    </td>
                    <td class="border p-3 text-gray-800">{{ $product->name }}</td>
                    <td class="border p-3 text-gray-800">${{ number_format($product->price, 2) }}</td>
                    <td class="border p-3 text-center">
                        <form method="POST" action="{{ route('cart.add', $product->id) }}">
                            @csrf
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-300">
                                Add to Cart
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center border p-4 text-gray-500">No products available.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
