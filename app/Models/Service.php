<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $table = "service";
    protected $fillable = [
        'name',
        'price',
        'desc',
        'type',
        'status',
        'thumbnail',
        'person_amount',
        'category_id',
        'shop_id'
    ];

    public function images()
    {
        return $this->hasMany(ImageService::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function bookmarkService()
    {
        return $this->hasMany(BookmarkService::class);
    }
    public function ratings()
    {
        return $this->hasMany(RatingService::class, 'service_id');
    }
    
}
