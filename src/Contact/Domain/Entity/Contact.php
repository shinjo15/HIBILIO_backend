<?php

declare(strict_types=1);

namespace Src\Contact\Domain\Entity;

use DateTimeImmutable;
use LogicException;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactIdentifier;
use Src\Contact\Domain\ValueObject\ContactSendStatus;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class Contact
{
    private function __construct(
        private readonly ContactIdentifier $contactIdentifier,
        private readonly AccountIdentifier $accountIdentifier,
        private readonly ContactTitle $title,
        private readonly ContactContent $content,
        private ?ContactSendStatus $sendStatus,
        private ?DateTimeImmutable $sentAt,
    ) {
        $this->assertStatusAndSentAt();
    }

    public static function create(ContactIdentifier $contactIdentifier, AccountIdentifier $accountIdentifier, ContactTitle $title, ContactContent $content): self
    {
        return new self($contactIdentifier, $accountIdentifier, $title, $content, null, null);
    }

    public static function restore(ContactIdentifier $contactIdentifier, AccountIdentifier $accountIdentifier, ContactTitle $title, ContactContent $content, ContactSendStatus $sendStatus, ?DateTimeImmutable $sentAt): self
    {
        return new self($contactIdentifier, $accountIdentifier, $title, $content, $sendStatus, $sentAt);
    }

    public function markSent(DateTimeImmutable $sentAt): void
    {
        if ($this->sendStatus !== null) {
            throw new LogicException('未確定のお問い合わせのみ送信済みにできます。');
        }

        $this->sendStatus = ContactSendStatus::Sent;
        $this->sentAt = $sentAt;
    }

    public function markFailed(): void
    {
        if ($this->sendStatus !== null) {
            throw new LogicException('未確定のお問い合わせのみ送信失敗にできます。');
        }

        $this->sendStatus = ContactSendStatus::Failed;
        $this->sentAt = null;
    }

    public function contactIdentifier(): ContactIdentifier
    {
        return $this->contactIdentifier;
    }

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

    public function sendStatus(): ?ContactSendStatus
    {
        return $this->sendStatus;
    }

    public function sentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    private function assertStatusAndSentAt(): void
    {
        if ($this->sendStatus === null && $this->sentAt !== null) {
            throw new LogicException('未確定のお問い合わせに送信日時は設定できません。');
        }
        if ($this->sendStatus === ContactSendStatus::Sent && $this->sentAt === null) {
            throw new LogicException('送信済みのお問い合わせには送信日時が必要です。');
        }
        if ($this->sendStatus === ContactSendStatus::Failed && $this->sentAt !== null) {
            throw new LogicException('送信失敗のお問い合わせに送信日時は設定できません。');
        }
    }
}
