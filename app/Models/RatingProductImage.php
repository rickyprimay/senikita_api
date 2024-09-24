<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingProductImage extends Model
{
    use HasFactory;

    protected $table = 'rating_product_image';

    protected $fillable = [
        'rating_product_id',
        'picture_rating_image'
    ];
}
