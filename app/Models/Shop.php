<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;
    protected $table = 'shop';
    protected $fillable = [
        'name',
        'desc',
        'lat',
        'lng',
        'address',
        'city_id',
        'province_id',
        'profile_picture',
        'user_id',
        'status',
        'balance'
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_shop', 'shop_id', 'category_id');
    }
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
    public function product()
    {
        return $this->hasMany(Product::class, 'shop_id');
    }
    
}
