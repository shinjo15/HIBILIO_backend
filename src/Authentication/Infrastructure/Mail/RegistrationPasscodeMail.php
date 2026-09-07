<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class RegistrationPasscodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly string $passcode) {}

    public function build(): self
    {
        return $this->subject('HIBILIO 登録パスコード')->text('mail.registration-passcode-text');
    }
}
