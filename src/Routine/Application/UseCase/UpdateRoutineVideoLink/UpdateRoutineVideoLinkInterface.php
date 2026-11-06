<?php

declare(strict_types=1);

namespace Src\Routine\Application\UseCase\UpdateRoutineVideoLink;

interface UpdateRoutineVideoLinkInterface
{
    public function execute(UpdateRoutineVideoLinkInputPort $input): UpdateRoutineVideoLinkOutputPort;
}
