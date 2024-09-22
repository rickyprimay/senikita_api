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
}
