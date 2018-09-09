<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Page extends Model {

    const PAGE_DEFAULT = 'index';

    protected $fillable = [
        'active', 'text', 'url', 'name'
    ];

    protected $dates = [];

    public static $rules = [
        // Validation rules
    ];

    // Relationships

}
