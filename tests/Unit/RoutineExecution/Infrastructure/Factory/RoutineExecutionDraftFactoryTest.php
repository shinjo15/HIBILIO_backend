<?php

declare(strict_types=1);

namespace Tests\Unit\RoutineExecution\Infrastructure\Factory;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\RoutineExecution\Infrastructure\Factory\RoutineExecutionDraftFactory;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class RoutineExecutionDraftFactoryTest extends TestCase
{
    public function test_creates_an_empty_draft_with_generated_identifier_and_current_time(): void
    {
        $uuid = $this->createMock(UuidServiceInterface::class);
        $uuid->expects(self::once())->method('generate')->willReturn('cccccccc-cccc-4ccc-8ccc-cccccccccccc');
        $factory = new RoutineExecutionDraftFactory($uuid);
        $account = new AccountIdentifier('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $routine = new RoutineIdentifier('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb');

        $before = new DateTimeImmutable;
        $draft = $factory->create($account, $routine);
        $after = new DateTimeImmutable;

        self::assertSame('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $draft->routineExecutionIdentifier()->value());
        self::assertSame($account, $draft->executorAccountIdentifier());
        self::assertSame($routine, $draft->routineIdentifier());
        self::assertGreaterThanOrEqual($before, $draft->executedAt()->value());
        self::assertLessThanOrEqual($after, $draft->executedAt()->value());
        self::assertNull($draft->routineExecutionMemo());
    }
}
