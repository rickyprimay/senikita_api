<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionService extends Model
{
    use HasFactory;

    protected $table = 'transaction_service';

    protected $fillable = [
        'service_id',
        'payment_status',
        'payment_date' 
    ];
}
