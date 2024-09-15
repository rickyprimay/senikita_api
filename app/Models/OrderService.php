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
        'address',
        'invoice_url',
        'optional_document',
        'status'
    ];
}
