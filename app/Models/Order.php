<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = "order";

    protected $fillable = [
        'user_id',
        'product_id',
        'qty',
        'no_transaction',
        'email',
        'name',
        'address',
        'ongkir',
        'price',
        'city_id',
        'province_id',
        'total_price',
        'invoice_url',
        'service',
        'courir',
        'status',
        'estimation'
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
