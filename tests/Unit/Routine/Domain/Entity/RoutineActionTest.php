<?php

declare(strict_types=1);

namespace Tests\Unit\Routine\Domain\Entity;

use PHPUnit\Framework\TestCase;
use Src\Routine\Domain\Entity\RoutineAction;
use Src\Routine\Domain\ValueObject\RoutineActionMemo;
use Src\Routine\Domain\ValueObject\RoutineActionMinutes;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineActionPosition;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineActionTest extends TestCase
{
    public function test_create_rejects_legacy_video_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RoutineAction::create(
            new RoutineActionIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7'), null,
            new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3'), new RoutineActionName('action'),
            null, null, new RoutineActionPosition(0), new RoutineVideoUrl('https://example.com/video'),
        );
    }

    public function test_retains_the_values_of_a_created_routine_action(): void
    {
        $routineActionIdentifier = new RoutineActionIdentifier('f6ca9f4c-169b-4b2d-a717-4a4f40d1490f');
        $parentRoutineActionIdentifier = new RoutineActionIdentifier('75017745-e475-4337-b0f3-3fc3d670e5c7');
        $routineIdentifier = new RoutineIdentifier('34b8d590-07cb-49ca-bfd9-cb9f40e26bd3');
        $routineActionName = new RoutineActionName('水を飲む');
        $routineActionMemo = new RoutineActionMemo('常温の水を飲む');
        $routineActionMinutes = new RoutineActionMinutes(1);

        $routineAction = RoutineAction::create(
            routineActionIdentifier: $routineActionIdentifier,
            parentRoutineActionIdentifier: $parentRoutineActionIdentifier,
            routineIdentifier: $routineIdentifier,
            routineActionName: $routineActionName,
            routineActionMemo: $routineActionMemo,
            routineActionMinutes: $routineActionMinutes,
            position: new RoutineActionPosition(2),
        );

        $this->assertSame($routineActionIdentifier, $routineAction->routineActionIdentifier());
        $this->assertSame($parentRoutineActionIdentifier, $routineAction->parentRoutineActionIdentifier());
        $this->assertSame($routineIdentifier, $routineAction->routineIdentifier());
        $this->assertSame($routineActionName, $routineAction->routineActionName());
        $this->assertSame($routineActionMemo, $routineAction->routineActionMemo());
        $this->assertSame($routineActionMinutes, $routineAction->routineActionMinutes());
        $this->assertSame(2, $routineAction->position()->value());
    }
}
