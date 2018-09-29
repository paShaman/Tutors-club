<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'users_payments';

    protected $fillable = [
        'amount', 'external_payment_id', 'user_id', 'charged_user_id', 'reason'
    ];

}