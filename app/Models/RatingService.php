<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingService extends Model
{
    use HasFactory;

    protected $table = 'rating_service';

    protected $fillable = [
        'user_id',
        'service_id',
        'rating',
        'comment',
    ];
}
