<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{

    // Fillable attributes to prevent mass-assignment vulnerabilities
    protected $fillable = [
        'user_id',
        'total_amount',
        'payment_method',
        'shipping_address',
        'status',
        'items'
    ];

    protected $casts = [
        'items' => 'array',  // Automatically decode JSON to array when retrieving
    ];
}
