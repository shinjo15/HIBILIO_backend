<?php

declare(strict_types=1);

namespace Src\Routine\Application\Usecase\Query\GetCustomizationOrigins;

interface GetCustomizationOriginsInterface
{
    public function execute(GetCustomizationOriginsInputPort $input): GetCustomizationOriginsOutputPort;
}
