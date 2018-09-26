<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    const PAGE_DEFAULT = 'cabinet';

    const NEED_AUTH = 1;
    const NO_NEED_AUTH = 2;

    protected $fillable = [
        'active', 'name', 'title', 'description', 'keywords', 'need_auth'
    ];

}