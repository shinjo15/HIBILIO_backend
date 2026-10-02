<?php

declare(strict_types=1);

namespace Src\Contact\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class ContactSupportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $accountIdentifier,
        public readonly string $replyToAddress,
        public readonly string $title,
        public readonly string $content,
    ) {}

    public function build(): self
    {
        return $this->from((string) config('mail.from.address'), (string) config('mail.from.name'))
            ->subject($this->title)
            ->replyTo($this->replyToAddress)
            ->text('mail.contact-support-text');
    }
}
