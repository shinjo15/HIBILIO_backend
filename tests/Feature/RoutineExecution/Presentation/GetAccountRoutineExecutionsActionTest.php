<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetAccountRoutineExecutionsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_an_empty_history_for_an_account_without_routine_executions(): void
    {
        $this->getJson('/api/accounts/11111111-1111-4111-8111-111111111111/routine-executions')
            ->assertOk()
            ->assertExactJson(['items' => [], 'total' => 0]);
    }
}
