<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineActionPosition;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Routine\Infrastructure\Factory\RoutineActionFactory;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineActionFactoryTest extends TestCase
{
    public function test_rejects_legacy_url_for_new_action(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new RoutineActionFactory)->create(
            new RoutineActionIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7'), null,
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'), new RoutineActionName('action'),
            null, null, new RoutineActionPosition(0), new RoutineVideoUrl('https://example.com/video'),
        );
    }

    public function test_creates_an_action_with_its_parent_action_identifier(): void
    {
        $routineAction = (new RoutineActionFactory)->create(
            new RoutineActionIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7'),
            new RoutineActionIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f'),
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'),
            new RoutineActionName('ストレッチ'),
            null,
            null,
            new RoutineActionPosition(1),
        );

        $this->assertSame('75017745-e475-4337-b0f3-3fc3d670e5c7', $routineAction->routineActionIdentifier()->value());
        $this->assertSame('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f', $routineAction->parentRoutineActionIdentifier()?->value());
        $this->assertSame(1, $routineAction->position()->value());
    }
}
