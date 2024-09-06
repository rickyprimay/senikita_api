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
        'city',
        'province',
        'profile_picture',
        'user_id',
        'status'
    ];
    
}
