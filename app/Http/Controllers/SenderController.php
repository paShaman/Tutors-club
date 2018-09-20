<?php

namespace App\Http\Controllers;

use App\Sender\Sender;
use Illuminate\Support\Facades\Auth;


class SenderController extends Controller
{
    /**
     * подписка
     */
    public function subscribe()
    {
        $senderId = request()->post('id');
        $senderType = request()->post('type');

        $result = Sender::subscribe(Auth::id(), $senderType, $senderId);

        if ($result === true) {
            return $this->_resultSuccess();
        }
        return $this->_resultSuccess($result);
    }

    /**
     * отмена подписки
     */
    public function unsubscribe()
    {
        $senderId = request()->post('id');
        $senderType = request()->post('type');

        $result = Sender::subscribe(Auth::id(), $senderType, $senderId, true);

        if ($result === true) {
            return $this->_resultSuccess();
        }
        return $this->_resultSuccess($result);
    }

    public function test()
    {
        $sender = new Sender();

        $sender->setUser(Auth::user());

        $sender->send(['заголовок', 'описание']);
    }
}