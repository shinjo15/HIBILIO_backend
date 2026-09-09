<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInputPort;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class CreateRoutineExecutionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_an_empty_list_of_executed_routine_actions_without_executing_the_use_case(): void
    {
        $createRoutineExecution = new class implements CreateRoutineExecutionInterface
        {
            public int $executionCount = 0;

            public function execute(CreateRoutineExecutionInputPort $input): void
            {
                $this->executionCount++;
            }
        };
        $this->app->instance(CreateRoutineExecutionInterface::class, $createRoutineExecution);
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
            }
        });

        $this->postJson('/api/routine-executions', [
            'routine_identifier' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'executed_routine_action_identifiers' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('executed_routine_action_identifiers');

        self::assertSame(0, $createRoutineExecution->executionCount);
    }
}
