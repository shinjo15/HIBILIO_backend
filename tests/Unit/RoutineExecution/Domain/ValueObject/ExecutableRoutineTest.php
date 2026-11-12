<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Domain\ValueObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\RoutineExecution\Domain\ValueObject\ExecutableRoutine;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ExecutableRoutineTest extends TestCase
{
    #[DataProvider('executionCases')]
    public function test_execution_rules(bool $expected, bool $own, bool $routineAvailable, bool $authorAvailable, AccountStatus $status, AccountVisibility $visibility, bool $follows, bool $viewerBlocks, bool $authorBlocks): void
    {
        $author = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $viewer = $own ? $author : new AccountIdentifier('22222222-2222-4222-8222-222222222222');
        $routine = new ExecutableRoutine($author, $viewer, $routineAvailable, $authorAvailable, $status, $visibility, $follows, $viewerBlocks, $authorBlocks);

        self::assertSame($expected, $routine->canBeExecuted());
    }

    public function test_execution_decision_does_not_accept_another_viewer(): void
    {
        self::assertSame(0, (new \ReflectionMethod(ExecutableRoutine::class, 'canBeExecuted'))->getNumberOfParameters());
        self::assertFalse(method_exists(ExecutableRoutine::class, 'canBeExecutedBy'));
    }

    public static function executionCases(): array
    {
        $active = AccountStatus::ACTIVE;
        $public = AccountVisibility::PUBLIC;
        $private = AccountVisibility::PRIVATE;

        return [
            'public' => [true, false, true, true, $active, $public, false, false, false],
            'private owner' => [true, true, true, true, $active, $private, false, false, false],
            'private followed' => [true, false, true, true, $active, $private, true, false, false],
            'private not followed' => [false, false, true, true, $active, $private, false, false, false],
            'unavailable routine' => [false, false, false, true, $active, $public, false, false, false],
            'unavailable author' => [false, false, true, false, $active, $public, false, false, false],
            'temporarily banned author' => [false, false, true, true, AccountStatus::TEMPORARILY_BANNED, $public, false, false, false],
            'permanently banned author' => [false, false, true, true, AccountStatus::PERMANENTLY_BANNED, $public, false, false, false],
            'viewer blocks author' => [false, false, true, true, $active, $private, true, true, false],
            'author blocks viewer' => [false, false, true, true, $active, $private, true, false, true],
        ];
    }
}
