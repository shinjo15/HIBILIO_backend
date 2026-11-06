<?php

declare(strict_types=1);

namespace Src\Routine\Application\UseCase\UpdateRoutineVideoLink;

use Src\Routine\Domain\Exception\RoutineNotFoundException;
use Src\Routine\Domain\Exception\RoutineNotOwnedException;
use Src\Routine\Domain\Repository\RoutineActionRepositoryInterface;
use Src\Routine\Domain\Repository\RoutineRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class UpdateRoutineVideoLink implements UpdateRoutineVideoLinkInterface
{
    public function __construct(private RoutineRepositoryInterface $routines, private RoutineActionRepositoryInterface $actions, private TransactionManagerInterface $transactions) {}

    public function execute(UpdateRoutineVideoLinkInputPort $input): UpdateRoutineVideoLinkOutputPort
    {
        $this->transactions->transaction(function () use ($input): void {
            $routine = $this->routines->find($input->routineIdentifier()) ?? throw new RoutineNotFoundException;
            if (! $routine->isOwnedBy($input->accountIdentifier())) {
                throw new RoutineNotOwnedException;
            }
            $actionIdentifier = $input->routineActionIdentifier();
            if ($actionIdentifier !== null) {
                $action = $this->actions->findForRoutine($input->routineIdentifier(), $actionIdentifier) ?? throw new RoutineNotFoundException;
                if ($input->videoUrlProvided()) {
                    $action->changeVideoUrl($input->videoUrl());
                    $this->actions->saveVideoLink($action);
                }

                return;
            }
            if ($input->videoUrlProvided()) {
                $routine->changeVideoUrl($input->videoUrl());
                $this->routines->saveVideoLink($routine);
            }
        });

        return new UpdateRoutineVideoLinkOutput;
    }
}
