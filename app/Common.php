<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class Common
{
    /**
     * Verify reCAPTCHA token.
     *
     * @param  array $post POST data containing 'recaptcha' token
     * @return bool
     */
    public static function checkRecaptcha(array $post): bool
    {
        $token = $post['recaptcha'] ?? '';

        if (empty($token)) {
            return false;
        }

        $secret = config('services.recaptcha.secret', '');
        $url = 'https://www.google.com/recaptcha/api/siteverify';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'secret'   => $secret,
                'response' => $token,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            return false;
        }

        $data = json_decode($response, true);

        return !empty($data['success']);
    }

    /**
     * Paginate a query builder.
     *
     * @param  Builder $sql        Query builder instance
     * @param  array   $pagination Pagination params ['page' => int, 'perPage' => int]
     * @return LengthAwarePaginator
     */
    public static function pagination(Builder $sql, array $pagination = []): LengthAwarePaginator
    {
        $page = (int) ($pagination['page'] ?? Paginator::resolveCurrentPage());
        $perPage = (int) ($pagination['perPage'] ?? 20);

        $total = $sql->getCountForPagination();
        $results = $sql->forPage($page, $perPage)->get();

        return new LengthAwarePaginator($results, $total, $perPage, $page);
    }
}