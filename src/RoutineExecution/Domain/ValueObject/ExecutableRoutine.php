<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\ValueObject;

use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ExecutableRoutine
{
    public function __construct(
        private AccountIdentifier $author,
        private AccountIdentifier $viewer,
        private bool $available,
        private bool $authorAvailable,
        private ?AccountStatus $authorStatus,
        private ?AccountVisibility $authorVisibility,
        private bool $viewerFollowsAuthor,
        private bool $viewerBlocksAuthor,
        private bool $authorBlocksViewer,
    ) {}

    public function canBeExecuted(): bool
    {
        return $this->available
            && $this->authorAvailable
            && $this->authorStatus === AccountStatus::ACTIVE
            && ! $this->viewerBlocksAuthor
            && ! $this->authorBlocksViewer
            && ($this->authorVisibility === AccountVisibility::PUBLIC
                || $this->author->value() === $this->viewer->value()
                || $this->viewerFollowsAuthor);
    }
}
