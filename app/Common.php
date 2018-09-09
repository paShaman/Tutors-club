<?php

namespace App;

class Common
{
    /**
     * check environment path
     *
     * @return string
     */
    public static function getAssetsPath()
    {
        if (app()->environment() == 'local') {
            return '/assets/';
        }
        return '/assets/build/';
    }
}
