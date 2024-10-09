<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = "address";

    protected $fillable = [
        'user_id',
        'label_address',
        'name',
        'phone',
        'address_detail',
        'province_id',
        'city_id',
        'postal_code',
        'note'
    ];

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
}
