<?php

declare(strict_types=1);

namespace Tests\Feature\RoutineExecution\Presentation;

use App\Http\Requests\RoutineExecution\GetRecentExecutedRoutinesRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines\GetRecentExecutedRoutinesInputPort;
use Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines\GetRecentExecutedRoutinesInterface;
use Src\RoutineExecution\Application\Usecase\Query\GetRecentExecutedRoutines\GetRecentExecutedRoutinesOutputPort;
use Tests\Support\InteractsWithAccountImageUrlService;
use Tests\TestCase;

final class GetRecentExecutedRoutinesActionTest extends TestCase
{
    use InteractsWithAccountImageUrlService;
    use RefreshDatabase;

    public function test_request_and_query_exchange_primitive_input_and_output_contracts(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $this->account($me);
        $parent = $this->routine(1, $me);
        $routine = $this->routine(2, $me);
        DB::table('routines')->where('routine_identifier', $routine)->update(['parent_routine_identifier' => $parent]);
        $this->execution(1, $routine, $me, '2026-01-01 00:00:00');
        $tag = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        DB::table('tags')->insert(['tag_identifier' => $tag, 'tag_name' => 'tag', 'available' => true]);
        DB::table('routine_tags')->insert(['routine_identifier' => $routine, 'tag_identifier' => $tag, 'available' => true]);
        $action = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $routine, 'action_name' => 'action', 'action_minutes' => null, 'available' => true]);

        $request = GetRecentExecutedRoutinesRequest::create('/', 'GET', ['page' => '1', 'number_of_items_per_page' => '2']);
        $input = $request->toInput($me);
        $this->assertInstanceOf(GetRecentExecutedRoutinesInputPort::class, $input);
        $this->assertSame($me, $input->accountIdentifier());
        $this->assertSame(1, $input->page());
        $this->assertSame(2, $input->numberOfItemsPerPage());
        $output = app(GetRecentExecutedRoutinesInterface::class)->execute($input);
        $this->assertInstanceOf(GetRecentExecutedRoutinesOutputPort::class, $output);
        $this->assertSame(1, $output->total());
        $this->assertSame([[
            'postIdentifier' => null, 'routineIdentifier' => $routine,
            'customizationOrigin' => ['routineIdentifier' => $parent, 'routineName' => 'routine', 'accountName' => 'name', 'iconImageUrl' => "https://images.example/accounts/{$me}/icon"],
            'accountIdentifier' => $me, 'accountName' => 'name', 'iconImageUrl' => "https://images.example/accounts/{$me}/icon",
            'postedAt' => null, 'routineName' => 'routine', 'routineExecutionMinutes' => null,
            'tags' => [['tagIdentifier' => $tag, 'tagName' => 'tag']],
            'routineActions' => [['routineActionIdentifier' => $action, 'actionName' => 'action', 'actionMinutes' => null]],
            'postLikeCount' => null, 'postSupportCount' => null, 'liked' => null,
            'executionCount' => 0, 'customizationCount' => 0, 'myExecutionCount' => 1,
            'lastExecutedAt' => '2026-01-01T00:00:00+00:00',
        ]], $output->items());
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/recent-executions')->assertOk()->assertExactJson([
            'items' => [[
                'post_identifier' => null, 'routine_identifier' => $routine,
                'customization_origin' => ['routine_identifier' => $parent, 'routine_name' => 'routine', 'account_name' => 'name', 'icon_image_url' => "https://images.example/accounts/{$me}/icon"],
                'account_identifier' => $me, 'account_name' => 'name', 'icon_image_url' => "https://images.example/accounts/{$me}/icon",
                'posted_at' => null, 'routine_name' => 'routine', 'routine_execution_minutes' => null,
                'tags' => [['tag_identifier' => $tag, 'tag_name' => 'tag']],
                'routine_actions' => [['routine_action_identifier' => $action, 'action_name' => 'action', 'action_minutes' => null]],
                'post_like_count' => null, 'post_support_count' => null, 'liked' => null,
                'execution_count' => 0, 'customization_count' => 0, 'my_execution_count' => 1,
                'last_executed_at' => '2026-01-01T00:00:00+00:00',
            ]], 'total' => 1,
        ]);
    }

    public function test_empty_list_and_authentication(): void
    {
        $this->getJson('/api/my/routines/recent-executions')->assertUnauthorized();
        $this->account('11111111-1111-4111-8111-111111111111');
        $this->withSession(['account_identifier' => '11111111-1111-4111-8111-111111111111'])
            ->getJson('/api/my/routines/recent-executions')->assertOk()->assertExactJson(['items' => [], 'total' => 0]);
    }

    public function test_aggregates_only_own_executions_and_paginates_ties_including_postless_routines(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $other = '22222222-2222-4222-8222-222222222222';
        $this->account($me);
        $this->account($other);
        $a = $this->routine(1, $me);
        $b = $this->routine(2, $other);
        $c = $this->routine(3, $other);
        $this->execution(1, $a, $me, '2026-01-01 10:00:00');
        $this->execution(2, $a, $me, '2026-01-02 10:00:00');
        $this->execution(3, $a, $other, '2026-01-05 10:00:00');
        $this->execution(4, $b, $me, '2026-01-02 10:00:00');
        $this->execution(5, $c, $other, '2026-01-06 10:00:00');
        $post = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        DB::table('posts')->insert(['post_identifier' => $post, 'routine_identifier' => $a, 'post_category' => 'routine', 'post_like_count' => 5, 'post_support_count' => 3, 'available' => true, 'created_at' => '2025-01-01 00:00:00']);
        DB::table('likes')->insert(['account_identifier' => $me, 'post_identifier' => $post]);
        foreach ([true, true, true, false] as $i => $available) {
            DB::table('posts')->insert(['post_identifier' => sprintf('bbbbbbbb-bbbb-4bbb-8bbb-%012d', $i), 'routine_identifier' => $a, 'post_category' => 'action', 'available' => $available]);
        }
        $this->withSession(['account_identifier' => $me]);
        $this->getJson('/api/my/routines/recent-executions?page=1&number_of_items_per_page=1')->assertOk()->assertExactJson([
            'items' => [[
                'post_identifier' => $post, 'routine_identifier' => $a, 'customization_origin' => null,
                'account_identifier' => $me, 'account_name' => 'name', 'icon_image_url' => "https://images.example/accounts/{$me}/icon",
                'posted_at' => '2025-01-01T00:00:00+00:00', 'routine_name' => 'routine', 'routine_execution_minutes' => null,
                'tags' => [], 'routine_actions' => [], 'post_like_count' => 5, 'post_support_count' => 3,
                'liked' => true, 'execution_count' => 3, 'customization_count' => 0,
                'my_execution_count' => 2, 'last_executed_at' => '2026-01-02T10:00:00+00:00',
            ]], 'total' => 2,
        ]);
        $this->getJson('/api/my/routines/recent-executions?page=2&number_of_items_per_page=1')->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('items.0.routine_identifier', $b)
            ->assertJsonPath('items.0.post_identifier', null)->assertJsonPath('items.0.posted_at', null)
            ->assertJsonPath('items.0.post_like_count', null)->assertJsonPath('items.0.post_support_count', null)
            ->assertJsonPath('items.0.liked', null)->assertJsonPath('items.0.my_execution_count', 1)
            ->assertJsonStructure(['items' => [['liked']]]);
        $this->getJson('/api/my/routines/recent-executions?page=0&number_of_items_per_page=121')->assertUnprocessable()->assertJsonValidationErrors(['page', 'number_of_items_per_page']);
    }

    public function test_representative_post_is_oldest_available_with_null_dates_last(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $this->account($me);
        $routine = $this->routine(1, $me);
        $this->execution(1, $routine, $me, '2026-01-01 00:00:00');
        foreach ([
            [1, null, true, 1, 2],
            [2, '2025-01-01 00:00:00', false, 3, 4],
            [3, '2025-02-01 00:00:00', true, 5, 6],
            [4, '2025-03-01 00:00:00', true, 7, 8],
        ] as [$number, $date, $available, $likes, $supports]) {
            DB::table('posts')->insert(['post_identifier' => sprintf('aaaaaaaa-aaaa-4aaa-8aaa-%012d', $number), 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => $available, 'created_at' => $date, 'post_like_count' => $likes, 'post_support_count' => $supports]);
        }
        DB::table('likes')->insert(['account_identifier' => $me, 'post_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-000000000004']);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/recent-executions')->assertOk()
            ->assertJsonPath('items.0.post_identifier', 'aaaaaaaa-aaaa-4aaa-8aaa-000000000003')
            ->assertJsonPath('items.0.posted_at', '2025-02-01T00:00:00+00:00')
            ->assertJsonPath('items.0.post_like_count', 5)->assertJsonPath('items.0.post_support_count', 6)
            ->assertJsonPath('items.0.liked', false);
        DB::table('likes')->insert(['account_identifier' => $me, 'post_identifier' => 'aaaaaaaa-aaaa-4aaa-8aaa-000000000003']);
        $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('items.0.liked', true);
    }

    public function test_visibility_and_availability_follow_likes(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $this->account($me);
        foreach (range(2, 8) as $i) {
            $author = sprintf('%08d-0000-4000-8000-000000000000', $i);
            $this->account($author);
            if (in_array($i, [2, 3], true)) {
                DB::table('accounts')->where('account_identifier', $author)->update(['visibility' => 'private']);
            }
            if ($i === 4) {
                DB::table('accounts')->where('account_identifier', $author)->update(['status' => 'temporarily_banned']);
            }
            if ($i === 5) {
                DB::table('accounts')->where('account_identifier', $author)->update(['available' => false]);
            }
            if ($i === 3) {
                DB::table('follows')->insert(['following_account_identifier' => $me, 'followed_account_identifier' => $author]);
            }
            if ($i === 6 || $i === 7) {
                DB::table('blocks')->insert(['blocking_account_identifier' => $i === 6 ? $me : $author, 'blocked_account_identifier' => $i === 6 ? $author : $me]);
            }
            $routine = $this->routine($i, $author);
            if ($i === 8) {
                DB::table('routines')->where('routine_identifier', $routine)->update(['available' => false]);
            }
            $this->execution($i, $routine, $me, '2026-01-01 00:00:00');
        }
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/recent-executions')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('items.0.routine_identifier', sprintf('99999999-9999-4999-8999-%012d', 3));
    }

    public function test_metadata_origin_and_representative_post_remain_stable(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $this->account($me);
        $parent = $this->routine(1, $me);
        $routine = $this->routine(2, $me);
        DB::table('routines')->where('routine_identifier', $routine)->update(['parent_routine_identifier' => $parent]);
        $this->execution(1, $routine, $me, '2026-01-01 00:00:00');
        $this->execution(2, $parent, $me, '2026-01-02 00:00:00');
        foreach ([1, 2] as $i) {
            DB::table('posts')->insert(['post_identifier' => sprintf('aaaaaaaa-aaaa-4aaa-8aaa-%012d', $i), 'routine_identifier' => $routine, 'post_category' => 'routine', 'available' => true, 'created_at' => '2025-01-01 00:00:00']);
        }
        $tag = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        DB::table('tags')->insert(['tag_identifier' => $tag, 'tag_name' => 'tag', 'available' => true]);
        DB::table('routine_tags')->insert(['routine_identifier' => $routine, 'tag_identifier' => $tag, 'available' => true]);
        $action = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
        DB::table('routine_actions')->insert(['routine_action_identifier' => $action, 'routine_identifier' => $routine, 'action_name' => 'action', 'action_minutes' => null, 'available' => true]);
        $this->withSession(['account_identifier' => $me])->getJson('/api/my/routines/recent-executions')->assertOk()
            ->assertJsonPath('total', 2)->assertJsonPath('items.0.routine_identifier', $parent)
            ->assertJsonPath('items.0.customization_count', 1)->assertJsonPath('items.1.routine_identifier', $routine)
            ->assertJsonPath('items.1.post_identifier', 'aaaaaaaa-aaaa-4aaa-8aaa-000000000001')
            ->assertJsonPath('items.1.customization_origin.routine_identifier', $parent)
            ->assertJsonPath('items.1.tags.0.tag_identifier', $tag)
            ->assertJsonPath('items.1.routine_actions.0.routine_action_identifier', $action)
            ->assertJsonPath('items.1.routine_actions.0.action_minutes', null);
        DB::table('routines')->where('routine_identifier', $parent)->update(['available' => false]);
        $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.customization_origin', null);
    }

    public function test_customization_origin_visibility_does_not_remove_child(): void
    {
        $me = '11111111-1111-4111-8111-111111111111';
        $author = '22222222-2222-4222-8222-222222222222';
        $this->account($me);
        $this->account($author);
        $parent = $this->routine(1, $author);
        $child = $this->routine(2, $me);
        DB::table('routines')->where('routine_identifier', $child)->update(['parent_routine_identifier' => $parent]);
        $this->execution(1, $child, $me, '2026-01-01 00:00:00');
        $this->withSession(['account_identifier' => $me]);

        DB::table('accounts')->where('account_identifier', $author)->update(['visibility' => 'private']);
        $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.routine_identifier', $child)->assertJsonPath('items.0.customization_origin', null)
            ->assertJsonStructure(['items' => [['customization_origin']]]);

        DB::table('follows')->insert(['following_account_identifier' => $me, 'followed_account_identifier' => $author]);
        $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.routine_identifier', $child)->assertJsonPath('items.0.customization_origin.routine_identifier', $parent);

        foreach ([[$me, $author], [$author, $me]] as [$blocking, $blocked]) {
            DB::table('blocks')->insert(['blocking_account_identifier' => $blocking, 'blocked_account_identifier' => $blocked]);
            $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('total', 1)
                ->assertJsonPath('items.0.routine_identifier', $child)->assertJsonPath('items.0.customization_origin', null);
            DB::table('blocks')->where('blocking_account_identifier', $blocking)->where('blocked_account_identifier', $blocked)->delete();
        }

        foreach ([['status' => 'temporarily_banned'], ['available' => false]] as $attributes) {
            DB::table('accounts')->where('account_identifier', $author)->update($attributes);
            $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('total', 1)
                ->assertJsonPath('items.0.routine_identifier', $child)->assertJsonPath('items.0.customization_origin', null);
            DB::table('accounts')->where('account_identifier', $author)->update(['status' => 'active', 'available' => true]);
        }
        DB::table('routines')->where('routine_identifier', $parent)->update(['available' => false]);
        $this->getJson('/api/my/routines/recent-executions')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('items.0.routine_identifier', $child)->assertJsonPath('items.0.customization_origin', null);
    }

    private function routine(int $number, string $author): string
    {
        $id = sprintf('99999999-9999-4999-8999-%012d', $number);
        DB::table('routines')->insert(['routine_identifier' => $id, 'account_identifier' => $author, 'routine_name' => 'routine', 'available' => true]);

        return $id;
    }

    private function execution(int $number, string $routine, string $executor, string $time): void
    {
        DB::table('routine_executions')->insert(['routine_execution_identifier' => sprintf('88888888-8888-4888-8888-%012d', $number), 'routine_identifier' => $routine, 'executor_account_identifier' => $executor, 'executed_at' => $time]);
    }

    private function account(string $id): void
    {
        DB::table('accounts')->insert(['account_identifier' => $id, 'account_name' => 'name', 'email_address' => $id.'@example.com', 'available' => true, 'status' => 'active']);
    }
}
