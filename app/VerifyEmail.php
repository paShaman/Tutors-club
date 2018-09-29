<?php

namespace App;

use App\Mail\EmailConfirmation;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends VerifyEmailBase
{
    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return EmailConfirmation|\Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $lng = app('translator')->getLocale();

        return (new MailMessage())
            ->subject(trans('mail.title.email-confirmation'))
            ->view('mail.'. $lng .'.email-confirmation', [
                'url' => $this->verificationUrl($notifiable)
            ])
        ;
    }
}
