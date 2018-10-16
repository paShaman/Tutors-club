<?php

namespace App\Model;

use App\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Payment extends Model
{
    protected $table = 'users_payments';

    protected $fillable = [
        'amount', 'external_payment_id', 'user_id', 'charged_user_id', 'reason'
    ];

    /*
     * FUNCTIONS ---------------------------------
     */

    /**
     * получаем списки пользователей
     *
     * @param $params
     * @param array $pagination
     * @return array
     */
    public static function getList($params = [], $pagination = [])
    {
        $sql = DB::table('users_payments as up')
            ->leftJoin('users as u1', 'up.user_id', '=', 'u1.id')
            ->leftJoin('users as u2', 'up.charged_user_id', '=', 'u2.id')
            ->orderBy('up.created_at', 'desc')
            ->select([
                'up.created_at',
                'up.amount',
                'up.external_payment_id',
                'up.reason',
                'u1.id',
                'u2.id as charged_id',
                DB::raw('concat(u1.first_name, " ", u1.last_name) as user'),
                DB::raw('concat(u2.first_name, " ", u2.last_name) as charged_user'),
            ])
        ;

        if (!empty($params['id'])) {
            $sql->where('u1.id', $params['id']);
        }

        if (!empty($params['user'])) {
            $sql->where(function ($query) use ($params){
                $query
                    ->where('u1.first_name', 'like', '%' . $params['user'] . '%')
                    ->orWhere('u1.last_name', 'like', '%' . $params['user'] . '%')
                ;
            });
        }

        if (!empty($params['charged_id'])) {
            $sql->where('u2.id', $params['charged_id']);
        }

        if (!empty($params['charged_user'])) {
            $sql->where(function ($query) use ($params){
                $query
                    ->where('u2.first_name', 'like', '%' . $params['charged_user'] . '%')
                    ->orWhere('u2.last_name', 'like', '%' . $params['charged_user'] . '%')
                ;
            });
        }

        if (!empty($params['reason'])) {
            $sql->where('up.reason', 'like', '%' . $params['reason'] . '%');
        }

        if (!empty($params['external_payment_id'])) {
            $sql->where('up.external_payment_id', 'like', '%' . $params['reason'] . '%');
        }

        if (!empty($params['order'])) {
            $sql->orderBy($params['order'], $params['order_way'] ?? 'asc');
        }

        if (!empty($pagination)) {
            return Common::pagination($sql, $pagination);
        }

        return $sql->get()->toArray();
    }
}