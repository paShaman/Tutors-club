<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class Common
{
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