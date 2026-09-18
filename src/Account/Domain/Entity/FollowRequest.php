<?php

declare(strict_types=1);

namespace Src\Account\Domain\Entity;

use Src\Account\Domain\Exception\SelfFollowException;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class FollowRequest
{
    public function __construct(private readonly AccountIdentifier $requestingAccountIdentifier, private readonly AccountIdentifier $targetAccountIdentifier, private FollowRequestStatus $status = FollowRequestStatus::PENDING)
    {
        if ($requestingAccountIdentifier->value() === $targetAccountIdentifier->value()) {
            throw new SelfFollowException;
        }
    }

    public function requestingAccountIdentifier(): AccountIdentifier
    {
        return $this->requestingAccountIdentifier;
    }

    public function targetAccountIdentifier(): AccountIdentifier
    {
        return $this->targetAccountIdentifier;
    }

    public function status(): FollowRequestStatus
    {
        return $this->status;
    }

    public function approve(): void
    {
        $this->status = FollowRequestStatus::APPROVED;
    }

    public function reject(): void
    {
        $this->status = FollowRequestStatus::REJECTED;
    }

    public function requestAgain(): void
    {
        $this->status = FollowRequestStatus::PENDING;
    }
}
