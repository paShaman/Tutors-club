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

    /**
     * проверка recaptcha
     *
     * @param $input
     * @return bool
     */
    public static function checkRecaptcha($input)
    {
        //проверка reCaptcha
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            //CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks,
            CURLOPT_POSTFIELDS => [
                'secret'    => env('RECAPTCHA_SECRET'),
                'response'  => !empty($input['g-recaptcha-response']) ? $input['g-recaptcha-response'] : ''
            ]
        );

        $ch      = curl_init( 'https://www.google.com/recaptcha/api/siteverify' );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        curl_close( $ch );

        $content = json_decode($content, true);

        if (empty($content['success'])) {
            return false;
        }
        return true;
    }
}
