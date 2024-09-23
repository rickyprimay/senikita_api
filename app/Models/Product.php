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
        'shop_id',
        'sold'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
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

    public function ratings()
    {
        return $this->hasMany(RatingProduct::class, 'product_id');
    }
    public function images()
    {
        return $this->hasMany(ImageProduct::class, 'product_id');
    }
    public function bookmark()
    {
        return $this->hasMany(BookmarkProduct::class);
    }

    public function cart()
    {
        return $this->hasMany(CartItem::class);
    }
}
