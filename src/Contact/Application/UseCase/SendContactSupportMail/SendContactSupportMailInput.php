<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class SendContactSupportMailInput implements SendContactSupportMailInputPort
{
    public function __construct(
        private AccountIdentifier $accountIdentifier,
        private ContactTitle $title,
        private ContactContent $content,
    ) {}

    public function accountIdentifier(): AccountIdentifier
    {
        return $this->accountIdentifier;
    }

    public function title(): ContactTitle
    {
        return $this->title;
    }

    public function content(): ContactContent
    {
        return $this->content;
    }
}
