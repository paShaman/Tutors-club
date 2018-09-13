<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Page extends Model {

    const PAGE_DEFAULT = 'cabinet';

    protected $fillable = [
        'active', 'name', 'title', 'description', 'keywords', 'need_auth'
    ];

}
