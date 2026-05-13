<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Social extends Model
{
    const SOCIAL_GOOGLE     = 'google';

    protected $table = 'users_social';

    protected $fillable = [
        'social', 'social_id', 'user_id'
    ];

}