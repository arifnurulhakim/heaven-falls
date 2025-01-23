<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendCodeResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $code;
    public $username;

    public function __construct($data)
    {
        $this->code = $data[0] ?? null;
        $this->username = $data[1] ?? null;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.send-code-reset-password')
            ->with([
                'code' => $this->code,
                'username' => $this->username,
            ]);
    }
}
