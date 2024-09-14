<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = "product";

    protected $fillable = [
        'name',
        'price',
        'desc',
        'stock',
        'status',
        'thumbnail',
        'category_id',
        'shop_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product')
                    ->withPivot('qty')
                    ->withTimestamps();
    }
}
