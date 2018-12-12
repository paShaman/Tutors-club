<?php

namespace App;

use Illuminate\Database\Query\Builder;

class Common
{
    const PAGINATION_PAGE_SIZE = 20;

    const BTN = 'btn waves-effect waves-light';

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

    /**
     * генерим корректный путь к файлу
     *
     * @param $filename
     * @param $extension
     * @param $returnWithDir
     * @return string|array
     */
    public static function generateFilePath($filename, $extension, $returnWithDir = false)
    {
        $md5 = md5($filename);

        $dir1 = substr($md5, 0, 2) . DIRECTORY_SEPARATOR;
        $dir2 = substr($md5, 2, 2) . DIRECTORY_SEPARATOR;

        $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;

        if (!is_dir($path . $dir1)) {
            mkdir($path . $dir1);
        }

        if (!is_dir($path . $dir1 . $dir2)) {
            mkdir($path . $dir1 . $dir2);
        }

        if ($returnWithDir) {
            return [$path . $dir1 .  $dir2 .  $md5 . '.' . $extension, 'upload' . DIRECTORY_SEPARATOR . $dir1 . $dir2];
        }
        return $path . $dir1 .  $dir2 .  $md5 . '.' . $extension;
    }

    /**
     * применяем пагинацию к запросу
     *
     * @param Builder $sql
     * @param $params
     */
    public static function pagination($sql, $params)
    {
        $limit = $params['page_size'] ?? self::PAGINATION_PAGE_SIZE;
        $offset = (($params['page'] ?? 1) - 1) * $limit;

        $count = $sql->count();

        $sql
            ->limit($limit)
            ->offset($offset)
        ;

        $items = $sql->get()->toArray();

        foreach ($items as &$item) {
            $item = (array)$item;
        }

        return ['itemsCount' => $count, 'data' => $items];
    }
}
