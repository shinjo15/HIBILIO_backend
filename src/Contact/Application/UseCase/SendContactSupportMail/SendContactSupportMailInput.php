<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class SendContactSupportMailInput implements SendContactSupportMailInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private string $title,
        private string $content,
    ) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function content(): string
    {
        return $this->content;
    }
}
