<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Payment;
use App\Model\User;

class PaymentController extends Controller
{
    /**
     * получаем список платежей
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentsList()
    {
        $page = request()->get('pageIndex');
        $pageSize = request()->get('pageSize');
        $sortField = request()->get('sortField');
        $sortOrder = request()->get('sortOrder');

        $params = [
            'id'                    => request()->get('id'),
            'user'                  => request()->get('user'),
            'charged_id'            => request()->get('charged_id'),
            'charged_user'          => request()->get('charged_user'),
            'reason'                => request()->get('reason'),
            'external_payment_id'   => request()->get('external_payment_id'),
            'order'                 => $sortField,
            'order_way'             => $sortOrder,
        ];

        $data = Payment::getList($params, [
            'page' => $page,
            'page_size' => $pageSize
        ]);

        return $this->_resultJson(true, $data);
    }

    /**
     * Добавление платежа
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentAdd()
    {
        $userId = request()->post('user_id');
        $amount = request()->post('amount');
        $reason = request()->post('reason');

        /**
         * @var $user User
         */
        $user = User::findOrFail($userId);

        $result = $user->addPayment($amount, $reason);

        if (empty($result)) {
            return $this->_resultError('Ошибка добавления платежа');
        }
        return $this->_resultSuccess('Платеж добавлен');
    }
}
