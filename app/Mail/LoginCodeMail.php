<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Kirish kodi emaili. Ataylab navbatga qo'yilmaydi: foydalanuvchi darhol olishi kerak (queue worker shart emas). */
class LoginCodeMail extends Mailable
{
    public function __construct(public readonly string $code, public readonly int $minutes) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.config('app.name').' sign-in code');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.login-code');
    }
}
