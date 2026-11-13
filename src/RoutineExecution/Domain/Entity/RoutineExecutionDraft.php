<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Entity;

use Src\RoutineExecution\Domain\ValueObject\ExecutedAt;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineExecutionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraft
{
    /** @var list<RoutineExecutionAction> */
    private array $actions = [];

    public function __construct(
        private readonly RoutineExecutionIdentifier $routineExecutionIdentifier,
        private readonly AccountIdentifier $executorAccountIdentifier,
        private readonly RoutineIdentifier $routineIdentifier,
        private readonly ExecutedAt $executedAt,
        private ?RoutineExecutionMemo $routineExecutionMemo,
    ) {}

    public function routineExecutionIdentifier(): RoutineExecutionIdentifier
    {
        return $this->routineExecutionIdentifier;
    }

    public function executorAccountIdentifier(): AccountIdentifier
    {
        return $this->executorAccountIdentifier;
    }

    public function routineIdentifier(): RoutineIdentifier
    {
        return $this->routineIdentifier;
    }

    public function executedAt(): ExecutedAt
    {
        return $this->executedAt;
    }

    public function routineExecutionMemo(): ?RoutineExecutionMemo
    {
        return $this->routineExecutionMemo;
    }

    public function changeMemo(?RoutineExecutionMemo $memo): void
    {
        $this->routineExecutionMemo = $memo;
    }

    /** @param list<RoutineExecutionAction> $actions */
    public function changeActions(array $actions): void
    {
        foreach ($actions as $action) {
            if ($action->routineExecutionIdentifier()->value() !== $this->routineExecutionIdentifier->value()) {
                throw new \InvalidArgumentException('実行記録が一致しません。');
            }
        }
        $this->actions = $actions;
    }

    /** @return list<RoutineExecutionAction> */
    public function actions(): array
    {
        return $this->actions;
    }

    public function changeAction(RoutineExecutionAction $action, bool $executed): void
    {
        if ($action->routineExecutionIdentifier()->value() !== $this->routineExecutionIdentifier->value()) {
            throw new \InvalidArgumentException('実行記録が一致しません。');
        }
        $this->actions = array_values(array_filter($this->actions, static fn (RoutineExecutionAction $existing): bool => $existing->routineActionIdentifier()->value() !== $action->routineActionIdentifier()->value()));
        if ($executed) {
            $this->actions[] = $action;
        }
    }

    public function hasAction(RoutineActionIdentifier $identifier): bool
    {
        foreach ($this->actions as $action) {
            if ($action->routineActionIdentifier()->value() === $identifier->value()) {
                return true;
            }
        }

        return false;
    }
}
