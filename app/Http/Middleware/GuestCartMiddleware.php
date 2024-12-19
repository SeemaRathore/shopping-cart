<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class GuestCartMiddleware
{
    public function handle($request, Closure $next)
    {
        if (auth()->check() && session()->has('cart_id')) {
            $guestCartId = session('cart_id');
            $guestCart = Cart::find($guestCartId);
            $userCart = Cart::firstOrCreate(['user_id' => auth()->id()]);

            if ($guestCart) {
                foreach ($guestCart->items as $item) {
                    $userCart->items()->updateOrCreate(
                        ['product_id' => $item->product_id],
                        ['quantity' => DB::raw('quantity + ' . $item->quantity), 'price' => $item->price]
                    );
                }
                $guestCart->delete();
            }

            session()->forget('cart_id');
        }

        return $next($request);
    }
}

