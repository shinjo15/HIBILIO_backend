<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use App\Http\Requests\RoutineExecution\GetMostExecutedRoutinesRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines\GetMostExecutedRoutinesInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines\GetMostExecutedRoutinesInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetMostExecutedRoutines\GetMostExecutedRoutinesOutputPort;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetMostExecutedRoutinesActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_empty_list_and_authentication(): void
    {
        $this->getJson('/api/my/routines/most-executed')->assertUnauthorized();
        $me = '11111111-1111-4111-8111-111111111111';
        DB::table('accounts')->insert(['account_identifier' => $me, 'account_name' => 'name', 'email_address' => 'me@example.com', 'available' => true, 'status' => 'active']);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/most-executed')
            ->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
    }

    public function test_counts_executions_for_the_viewer_and_paginates_stable_ties(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        $this->account($me);
        $this->account($other);
        $a = $this->routine(1, $me);
        $b = $this->routine(2, $other);
        $c = $this->routine(3, $other);
        $d = $this->routine(4, $other);
        foreach ([[$a, $me], [$b, $me], [$c, $me], [$c, $me], [$d, $other], [$b, $other], [$b, $other], [$b, $other]] as $i => [$routine, $executor]) {
            $this->execution($i, $routine, $executor);
        }
        $this->withSession(['account_identifier' => $me]);
        foreach ([$c, $a, $b] as $index => $id) {
            $this->getJson('/api/my/routines/most-executed?page='.($index + 1).'&number_of_items_per_page=1')
                ->assertOk()->assertJsonPath('total', 3)->assertJsonPath('items.0.routine_identifier', $id)
                ->assertJsonPath('items.0.my_execution_count', $index === 0 ? 2 : 1)
                ->assertJsonPath('items.0.post_identifier', null)->assertJsonPath('items.0.posted_at', null)
                ->assertJsonPath('items.0.post_like_count', null)->assertJsonPath('items.0.post_support_count', null)
                ->assertJsonPath('items.0.liked', null)->assertJsonMissingPath('items.0.last_executed_at');
        }
        $this->getJson('/api/my/routines/most-executed?page=0&number_of_items_per_page=121')
            ->assertUnprocessable()->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    public function test_contract_representative_post_and_metadata(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $this->account($me);
        $parent = $this->routine(1, $me);
        $child = $this->routine(2, $me);
        DB::table('routines')->where('routine_identifier', $child)->update(['parent_routine_identifier' => $parent]);
        $this->execution(1, $child, $me);
        $tag = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $action = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        DB::table('tags')->insert(['tag_identifier' => $tag, 'tag_name' => 'tag', 'available' => true]);
        DB::table('routine_tags')->insert(['routine_identifier' => $child, 'tag_identifier' => $tag, 'available' => true]);
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $child, 'action_name' => 'action', 'action_minutes' => null, 'position' => 0, 'available' => true]);
        foreach ([[1, null, true], [2, '2025-01-01 00:00:00', false], [3, '2025-02-01 00:00:00', true], [4, '2025-02-01 00:00:00', true]] as [$n, $date, $available]) {
            DB::table('posts')->insert(['post_identifier' => sprintf('aaaaaaaa-aaaa-4aaa-8aaa-%012d', $n), 'routine_identifier' => $child, 'post_category' => 'routine', 'available' => $available, 'created_at' => $date, 'post_like_count' => 5, 'post_support_count' => 3]);
        }
        $post = 'aaaaaaaa-aaaa-4aaa-8aaa-000000000003';
        DB::table('likes')->insert(['account_identifier' => $me, 'post_identifier' => $post]);
        foreach ([true, true, false] as $i => $available) {
            DB::table('posts')->insert(['post_identifier' => sprintf('dddddddd-dddd-4ddd-8ddd-%012d', $i), 'routine_identifier' => $child, 'post_category' => 'action', 'available' => $available]);
        }
        $request = GetMostExecutedRoutinesRequest::create('/', 'GET', ['page' => '1', 'number_of_items_per_page' => '2']);
        $input = $request->toInput($me);
        $this->assertInstanceOf(GetMostExecutedRoutinesInputPort::class, $input);
        $this->assertSame($me, $input->accountIdentifier());
        $this->assertSame(1, $input->page());
        $this->assertSame(2, $input->numberOfItemsPerPage());
        $output = app(GetMostExecutedRoutinesInterface::class)->execute($input);
        $this->assertInstanceOf(GetMostExecutedRoutinesOutputPort::class, $output);
        $this->assertSame(1, $output->total());
        $this->assertSame(1, $output->items()[0]['myExecutionCount']);
        $this->assertArrayNotHasKey('lastExecutedAt', $output->items()[0]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/most-executed')->assertOk()->assertExactJson([
            'items' => [[
                'post_identifier' => $post, 'routine_identifier' => $child,
                'customization_origin' => ['routine_identifier' => $parent, 'routine_name' => 'routine', 'account_name' => 'name', 'icon_image_url' => "https://images.example/accounts/{$me}/icon"],
                'account_identifier' => $me, 'account_name' => 'name', 'icon_image_url' => "https://images.example/accounts/{$me}/icon",
                'posted_at' => '2025-02-01T00:00:00+00:00', 'routine_name' => 'routine', 'routine_execution_minutes' => null,
                'tags' => [['tag_identifier' => $tag, 'tag_name' => 'tag']],
                'routine_actions' => [['routine_action_identifier' => $action, 'action_name' => 'action', 'action_minutes' => null]],
                'post_like_count' => 5, 'post_support_count' => 3, 'liked' => true,
                'execution_count' => 2, 'customization_count' => 0, 'my_execution_count' => 1,
            ]], 'total' => 1,
        ]);
    }

    public function test_visibility_and_parent_privacy(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $this->account($me);
        foreach (range(2, 8) as $i) {
            $author = sprintf('%08d-0000-4000-8000-000000000000', $i);
            $this->account($author);
            if (in_array($i, [2, 3], true)) {
                DB::table('accounts')->where('account_identifier', $author)->update(['visibility' => 'private']);
            }
            if ($i === 3) {
                DB::table('follows')->insert(['following_account_identifier' => $me, 'followed_account_identifier' => $author]);
            }
            if ($i === 4) {
                DB::table('accounts')->where('account_identifier', $author)->update(['status' => 'temporarily_banned']);
            }
            if ($i === 5) {
                DB::table('accounts')->where('account_identifier', $author)->update(['available' => false]);
            }
            if ($i === 6 || $i === 7) {
                DB::table('blocks')->insert(['blocking_account_identifier' => $i === 6 ? $me : $author, 'blocked_account_identifier' => $i === 6 ? $author : $me]);
            }
            $routine = $this->routine($i, $author);
            if ($i === 8) {
                DB::table('routines')->where('routine_identifier', $routine)->update(['available' => false]);
            }
            $this->execution($i, $routine, $me);
        }
        $parent = sprintf('99999999-9999-4999-8999-%012d', 2);
        $child = $this->routine(9, $me);
        DB::table('routines')->where('routine_identifier', $child)->update(['parent_routine_identifier' => $parent]);
        $this->execution(9, $child, $me);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/most-executed')->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('items.0.routine_identifier', sprintf('99999999-9999-4999-8999-%012d', 3))
            ->assertJsonPath('items.1.routine_identifier', $child)->assertJsonPath('items.1.customization_origin', null);
        $author = sprintf('%08d-0000-4000-8000-000000000000', 2);
        DB::table('follows')->insert(['following_account_identifier' => $me, 'followed_account_identifier' => $author]);
        $this->getJson('/api/my/routines/most-executed')->assertOk()->assertJsonPath('total', 3)
            ->assertJsonPath('items.2.customization_origin.routine_identifier', $parent);
        DB::table('blocks')->insert(['blocking_account_identifier' => $author, 'blocked_account_identifier' => $me]);
        $this->getJson('/api/my/routines/most-executed')->assertOk()->assertJsonPath('total', 2)
            ->assertJsonPath('items.1.customization_origin', null);
    }

    private function account(string $id): void
    {
        DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => 'name', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active']);
    }

    private function routine(int $number, string $author): string
    {
        $id = sprintf('99999999-9999-4999-8999-%012d', $number);
        DB::table('routines')->insert(['routine_identifier' => $id, 'account_identifier' => $author, 'routine_name' => 'routine', 'available' => true]);

        return $id;
    }

    private function execution(int $number, string $routine, string $executor): void
    {
        DB::table('routine_executions')->insert(['routine_execution_identifier' => sprintf('88888888-8888-4888-8888-%012d', $number), 'routine_identifier' => $routine, 'executor_account_identifier' => $executor, 'executed_at' => '2026-01-01 00:00:00']);
    }
}
