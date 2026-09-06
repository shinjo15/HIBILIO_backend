<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Application\UseCase\CreateRoutineExecution;

use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class CreateRoutineExecutionInput implements CreateRoutineExecutionInputPort
{
    /** @param list<RoutineActionIdentifier> $executedRoutineActionIdentifiers */
    public function __construct(
        private AccountIdentifier $executorAccountIdentifier,
        private RoutineIdentifier $routineIdentifier,
        private array $executedRoutineActionIdentifiers,
        private ?RoutineExecutionMemo $routineExecutionMemo,
    ) {
        if (! array_is_list($executedRoutineActionIdentifiers)) {
            throw new \InvalidArgumentException('実行した行動は一覧で指定する必要があります。');
        }

        $identifiers = [];

        foreach ($executedRoutineActionIdentifiers as $routineActionIdentifier) {
            if (! $routineActionIdentifier instanceof RoutineActionIdentifier) {
                throw new \InvalidArgumentException('実行した行動にはRoutineActionIdentifierのみ指定できます。');
            }

            if (in_array($routineActionIdentifier->value(), $identifiers, true)) {
                throw new \InvalidArgumentException('実行した行動は重複して指定できません。');
            }

            $identifiers[] = $routineActionIdentifier->value();
        }
    }

    public function executorAccountIdentifier(): AccountIdentifier
    {
        return $this->executorAccountIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function executedRoutineActionIdentifiers(): array
    {
        return $this->executedRoutineActionIdentifiers;
    }

    public function routineExecutionMemo(): ?RoutineExecutionMemo
    {
        return $this->routineExecutionMemo;
    }
}
