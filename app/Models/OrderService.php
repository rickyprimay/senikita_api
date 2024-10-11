<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderService extends Model
{
    use HasFactory;

    protected $table = 'order_service';

    protected $fillable = [
        'user_id',
        'service_id',
        'name',
        'email',
        'no_transaction',
        'price',
        'phone',
        'activity_date',
        'activity_name',
        'attendee',
        'province_id',
        'city_id',
        'activity_time',
        'description',
        'address',
        'invoice_url',
        'optional_document',
        'status',
        'status_order',
        'qty',
        'name',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function transaction()
    {
        return $this->hasOne(TransactionService::class, 'order_service_id', 'id');
    }

    public function province()
    {
        return $this->belongsTo(Province::class, 'province_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
