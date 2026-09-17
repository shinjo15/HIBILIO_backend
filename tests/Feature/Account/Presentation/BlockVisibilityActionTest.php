<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInput;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInterface;
use Src\Shared\Domain\Exception\BlockedAccountVisibilityException;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class BlockVisibilityActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_returns_not_found_for_every_direct_account_api_when_either_side_has_blocked_the_other(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $targetIdentifier = '22222222-2222-4222-8222-222222222222';
        $this->account($viewerIdentifier, '閲覧者');
        $this->account($targetIdentifier, '対象');
        $this->block($targetIdentifier, $viewerIdentifier);

        foreach ([
            "/api/accounts/{$targetIdentifier}/posts",
            "/api/accounts/{$targetIdentifier}/routine-executions",
            "/api/accounts/{$targetIdentifier}/likes",
            "/api/accounts/{$targetIdentifier}/following",
        ] as $url) {
            $this->withSession(['account_identifier' => $viewerIdentifier])
                ->getJson($url)
                ->assertNotFound();
        }
    }

    public function test_returns_not_found_for_every_direct_routine_api_when_the_viewer_is_blocked_by_the_routine_author(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $this->account($viewerIdentifier, '閲覧者');
        $this->account($authorIdentifier, 'Routine作成者');
        $this->routine($routineIdentifier, $authorIdentifier, null, '対象Routine');
        $this->block($authorIdentifier, $viewerIdentifier);

        foreach ([
            "/api/routines/{$routineIdentifier}",
            "/api/routines/{$routineIdentifier}/execution-posts",
            "/api/routines/{$routineIdentifier}/customized",
        ] as $url) {
            $this->withSession(['account_identifier' => $viewerIdentifier])
                ->getJson($url)
                ->assertNotFound();
        }
    }

    public function test_throws_a_visibility_exception_when_the_customized_routines_parent_author_is_blocked(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $authorIdentifier = '22222222-2222-4222-8222-222222222222';
        $routineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $this->account($viewerIdentifier, '閲覧者');
        $this->account($authorIdentifier, 'Routine作成者');
        $this->routine($routineIdentifier, $authorIdentifier, null, '対象Routine');
        $this->block($authorIdentifier, $viewerIdentifier);

        $this->expectException(BlockedAccountVisibilityException::class);

        $this->app->make(GetCustomizedRoutinesInterface::class)->execute(new GetCustomizedRoutinesInput(
            parentRoutineIdentifier: $routineIdentifier,
            page: 1,
            numberOfItemsPerPage: 20,
            viewerAccountIdentifier: $viewerIdentifier,
        ));
    }

    public function test_excludes_blocked_execution_and_customization_authors_from_routine_lists(): void
    {
        $viewerIdentifier = '11111111-1111-4111-8111-111111111111';
        $routineAuthorIdentifier = '22222222-2222-4222-8222-222222222222';
        $blockedContributorIdentifier = '33333333-3333-4333-8333-333333333333';
        $visibleContributorIdentifier = '44444444-4444-4444-8444-444444444444';
        $routineIdentifier = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $this->account($viewerIdentifier, '閲覧者');
        $this->account($routineAuthorIdentifier, 'Routine作成者');
        $this->account($blockedContributorIdentifier, 'Block対象');
        $this->account($visibleContributorIdentifier, '公開対象');
        $this->routine($routineIdentifier, $routineAuthorIdentifier, null, '親Routine');
        $this->routine('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $blockedContributorIdentifier, $routineIdentifier, '隠すカスタマイズ');
        $this->routine('cccccccc-cccc-4ccc-8ccc-cccccccccccc', $visibleContributorIdentifier, $routineIdentifier, '見せるカスタマイズ');
        $this->execution('dddddddd-dddd-4ddd-8ddd-dddddddddddd', $routineIdentifier, $blockedContributorIdentifier);
        $this->execution('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $routineIdentifier, $visibleContributorIdentifier);
        $this->executionPost('ffffffff-ffff-4fff-8fff-ffffffffffff', $routineIdentifier, 'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
        $this->executionPost('12121212-1212-4121-8121-121212121212', $routineIdentifier, 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee');
        $this->block($viewerIdentifier, $blockedContributorIdentifier);

        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/routines/{$routineIdentifier}/execution-posts")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.account_identifier', $visibleContributorIdentifier);
        $this->withSession(['account_identifier' => $viewerIdentifier])
            ->getJson("/api/routines/{$routineIdentifier}/customized")
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.account_identifier', $visibleContributorIdentifier);
    }

    private function account(string $identifier, string $name): void
    {
        DB::table('accounts')->insert([
            'account_identifier' => $identifier,
            'account_name' => $name,
            'email_address' => "{$identifier}@example.com",
            'available' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function block(string $blockingAccountIdentifier, string $blockedAccountIdentifier): void
    {
        DB::table('blocks')->insert([
            'blocking_account_identifier' => $blockingAccountIdentifier,
            'blocked_account_identifier' => $blockedAccountIdentifier,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function routine(string $identifier, string $accountIdentifier, ?string $parentRoutineIdentifier, string $name): void
    {
        DB::table('routines')->insert([
            'routine_identifier' => $identifier,
            'account_identifier' => $accountIdentifier,
            'parent_routine_identifier' => $parentRoutineIdentifier,
            'routine_name' => $name,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function execution(string $identifier, string $routineIdentifier, string $executorAccountIdentifier): void
    {
        DB::table('routine_executions')->insert([
            'routine_execution_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'executor_account_identifier' => $executorAccountIdentifier,
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function executionPost(string $identifier, string $routineIdentifier, string $routineExecutionIdentifier): void
    {
        DB::table('posts')->insert([
            'post_identifier' => $identifier,
            'routine_identifier' => $routineIdentifier,
            'routine_execution_identifier' => $routineExecutionIdentifier,
            'post_category' => 'action',
            'post_like_count' => 0,
            'post_support_count' => 0,
            'available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
