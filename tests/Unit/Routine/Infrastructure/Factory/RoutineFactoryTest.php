<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\ValueObject\RoutineActionIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Infrastructure\Factory\RoutineFactory;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineFactoryTest extends TestCase
{
    public function test_creates_a_routine_from_its_valid_domain_values(): void
    {
        $routine = (new RoutineFactory)->create(
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            null,
            new RoutineName('朝の集中ルーティン'),
            new RoutineActionIdentifiers([
                new RoutineActionIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            ]),
            null,
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            null,
            null,
        );

        $this->assertSame('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3', $routine->routineIdentifier()->value());
        $this->assertSame('朝の集中ルーティン', $routine->routineName()->value());
    }
}
