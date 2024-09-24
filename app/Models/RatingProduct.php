<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingProduct extends Model
{
    use HasFactory;

    protected $table = 'rating_product';

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ratingImages()
    {
        return $this->hasMany(RatingProductImage::class, 'rating_product_id');
    }
}
