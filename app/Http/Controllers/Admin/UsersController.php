<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\User;
use Illuminate\Support\Facades\URL;

class UsersController extends Controller
{
    /**
     * получаем список пользователей
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function usersList()
    {
        $page = request()->get('pageIndex');
        $pageSize = request()->get('pageSize');
        $sortField = request()->get('sortField');
        $sortOrder = request()->get('sortOrder');

        $params = [
            'id'            => request()->get('id'),
            'email'         => request()->get('email'),
            'first_name'    => request()->get('first_name'),
            'last_name'     => request()->get('last_name'),
            'middle_name'   => request()->get('middle_name'),
            'order'         => $sortField,
            'order_way'     => $sortOrder,
        ];

        $data = User::getList($params, [
            'page' => $page,
            'page_size' => $pageSize
        ]);

        //для пользователй генерим урлы для автоматической авторизации
        foreach ($data['data'] as &$user) {
            $user['force_login'] = URL::temporarySignedRoute('auth',
                now()->addMinutes(30),
                ['user' => $user['id']]);
        }

        return $this->_resultJson(true, $data);
    }
}
