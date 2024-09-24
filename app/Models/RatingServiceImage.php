<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingServiceImage extends Model
{
    use HasFactory;

    protected $table = 'rating_service_image';

    protected $fillable = [
        'rating_service_id',
        'picture_rating_service'
    ];
}
