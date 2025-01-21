<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogBalance extends Model
{
    protected $table = 'log_balances';

    protected $fillable = [
        'user_id',
        'bank_account_id',
        'message',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }
}
