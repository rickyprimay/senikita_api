<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingService extends Model
{
    use HasFactory;

    protected $table = 'service_id';

    protected $fillable = [
        'user_id',
        'service_id',
        'rating',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ratingImages()
    {
        return $this->hasMany(RatingServiceImage::class, 'rating_service_id');
    }
}
