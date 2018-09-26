<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'users_account';

    protected $fillable = [
        'social', 'social_id', 'user_id'
    ];

}