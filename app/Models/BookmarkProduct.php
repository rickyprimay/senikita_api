<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookmarkProduct extends Model
{
    use HasFactory;

    protected $table = 'bookmark_product';

    protected $fillable = [
        'user_id',
        'product_id'
    ];
}
