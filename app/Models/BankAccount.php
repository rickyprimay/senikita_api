<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
