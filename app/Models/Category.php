<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $table = 'category';

    protected $fillable = [
        'name'
    ];
    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'category_shop', 'category_id', 'shop_id');
    }
}
