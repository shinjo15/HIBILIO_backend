<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Infrastructure\Factory;

use DateTimeImmutable;
use Src\RoutineExecution\Domain\Entity\RoutineExecutionDraft;
use Src\RoutineExecution\Domain\Factory\RoutineExecutionDraftFactoryInterface;
use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class RoutineExecutionDraftFactory implements RoutineExecutionDraftFactoryInterface
{
    public function __construct(
        private UuidServiceInterface $uuidService,
    ) {}

    public function create(
        AccountIdentifier $executorAccountIdentifier,
        RoutineIdentifier $routineIdentifier,
    ): RoutineExecutionDraft {
        return new RoutineExecutionDraft(
            new RoutineExecutionIdentifier($this->uuidService->generate()),
            $executorAccountIdentifier,
            $routineIdentifier,
            new ExecutedAt(new DateTimeImmutable),
            null,
        );
    }
}
