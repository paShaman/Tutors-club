<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable as MailableBase;
use Illuminate\Queue\SerializesModels;

class Mailable extends MailableBase
{
    use Queueable, SerializesModels;

    protected $_data;
    protected $_view;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * установка шаблона рассылки
     *
     * @param $view
     */
    public function setView($view)
    {
        $this->_view = $view;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $lng = app('translator')->getLocale();

        $view = "mail.{$lng}." . $this->_view;

        $this->subject = trans('mail.title.' . $this->_view);

        return $this->view($view)
            ->with($this->_data);
    }
}
