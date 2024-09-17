<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookmarkService extends Model
{
    use HasFactory;

    protected $table = 'bookmark_service';

    protected $fillable = [
        'user_id',
        'service_id'
    ];
}
