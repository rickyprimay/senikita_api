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
        'no_transaction',
        'email',
        'name',
        'address',
        'ongkir',
        'price',
        'city_id',
        'province_id',
        'address_id',
        'total_price',
        'invoice_url',
        'service',
        'courier',
        'status',
        'status_order',
        'estimation',
        'note',
    ];
    protected $casts = [
        'product_ids' => 'array',
    ];
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }
    public function product()
    {
        return $this->belongsToMany(Product::class, 'order_product', 'order_id', 'product_id')
            ->withPivot('qty');
    }
    public function transaction()
    {
        return $this->hasOne(TransactionOrder::class, 'order_id');
    }
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
}
