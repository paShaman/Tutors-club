<?php

namespace App\Sender;

use App\User;

class Sender
{
    protected $_user;

    /**
     * subscribe/unsubscribe actions
     *
     * @param $userId
     * @param $senderType
     * @param $senderId
     * @param bool $unsubscribe
     * @return bool
     */
    public static function subscribe($userId, $senderType, $senderId, $unsubscribe = false)
    {
        try {
            if (empty($userId) || empty($senderType)) {
                throw new \Exception('empty_params');
            }

            $user = User::find($userId);

            if (empty($user)) {
                throw new \Exception('user_not_found');
            }

            $senderType = self::getSenderType($senderType);

            $senders = $user->senders();

            $exist = $senders->where([
                ['users_senders.sender', $senderType],
                ['users_senders.sender_id', $senderId]
            ]);

            if ($exist->first()) {
                if (!$unsubscribe) {
                    throw new \Exception('sender_exists');
                } else {
                    $exist->delete();
                }
            } else {
                if ($unsubscribe) {
                    throw new \Exception('sender_not_exists');
                } else {
                    $sender = new \App\Model\Sender();

                    $sender->sender = $senderType;
                    $sender->sender_id = $senderId;

                    $senders->save($sender);
                }
            }

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * из убпрощенных типов получаем полноценные
     *
     * @param $senderType
     * @return string
     */
    public static function getSenderType($senderType)
    {
        if ($senderType == 'webpush') {
            return \App\Model\Sender::SENDER_ONESIGNAL;
        }

        return $senderType;
    }

    /**
     * @param $user
     */
    public function setUser($user)
    {
        $this->_user = $user;
    }

    /**
     * установка юзера по его идентификатору
     *
     * @param $userId
     */
    public function setUserById($userId)
    {
        $this->_user = User::find($userId);
    }

    /**
     * функция отправки
     */
    public function send($message)
    {
        //пока только веб пуш @todo
        $provider = new WebPush();

        $provider->_user = $this->_user;

        $provider->send($message);
    }
}