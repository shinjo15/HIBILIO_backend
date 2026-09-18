<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ApproveFollowRequest;

use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ApproveFollowRequestInput implements ApproveFollowRequestInputPort
{
    public function __construct(private AccountIdentifier $targetAccountIdentifier, private AccountIdentifier $requestingAccountIdentifier) {}

    public function targetAccountIdentifier(): AccountIdentifier
    {
        return $this->targetAccountIdentifier;
    }

    public function requestingAccountIdentifier(): AccountIdentifier
    {
        return $this->requestingAccountIdentifier;
    }
}
