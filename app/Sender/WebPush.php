<?php

namespace App\Sender;

class WebPush extends Sender
{
    /**
     * simple force send
     *
     * @param $message
     * @return bool|void
     */
    public function send($message)
    {
        $headings = array(
            "en" => $message[0],
            "ru" => $message[0],
        );

        $content = array(
            "en" => $message[1],
            "ru" => $message[1],
        );

        $ids = $this->_user->senders()->where('users_senders.sender', \App\Model\Sender::SENDER_ONESIGNAL)->pluck('sender_id');

        $fields = [
            'app_id' => env('ONESIGNAL_APP'),
            'include_player_ids' => $ids,
            'contents' => $content,
            'headings' => $headings,
        ];

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);

        if (!empty($response['errors'])) {
            return false;
        }
        return true;
    }
}