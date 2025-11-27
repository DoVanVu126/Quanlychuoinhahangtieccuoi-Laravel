<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $expiresInMinutes;

    public function __construct($otpCode, $expiresInMinutes = 5)
    {
        $this->otpCode = $otpCode;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    public function build()
    { 
        return $this->subject('Mã OTP Lấy Lại Mật Khẩu')
                    ->view('emails.otp');
    }
}