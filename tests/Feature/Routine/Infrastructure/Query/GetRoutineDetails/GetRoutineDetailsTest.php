<?php

declare(strict_types=1);

namespace Tests\Feature\Routine\Infrastructure\Query\GetRoutineDetails;

use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInterface;
use Src\Routine\Infrastructure\Query\GetRoutineDetails\GetRoutineDetails;
use Tests\TestCase;

final class GetRoutineDetailsTest extends TestCase
{
    public function test_query_interface_is_bound_to_the_infrastructure_query(): void
    {
        self::assertInstanceOf(GetRoutineDetails::class, $this->app->make(GetRoutineDetailsInterface::class));
    }
}
