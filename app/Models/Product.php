<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'quantity'
    ];

    public function getImageAttribute($value): string
    {
        return $value ?: 'https://via.placeholder.com/150';
    }
}
