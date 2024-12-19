@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Order Complete</h1>
        <p>Thank you for your order. Your order ID is {{ $order->id }}.</p>
        <p>Total: ${{ number_format($order->total_amount, 2) }}</p>
        <p>Shipping Address: {{ $order->shipping_address }}</p>
    </div>
@endsection
