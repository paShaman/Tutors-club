<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sender extends Model {

    const SENDER_ONESIGNAL   = 'webpush_onesignal';

    protected $table = 'users_senders';

    protected $fillable = [
        'sender', 'sender_id', 'user_id'
    ];

}