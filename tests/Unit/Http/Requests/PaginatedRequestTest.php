<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Account\GetAccountRoutinePostsRequest;
use App\Http\Requests\Account\GetFavoriteTagPostsRequest;
use App\Http\Requests\Account\GetFollowingPostsRequest;
use App\Http\Requests\Account\GetMyRoutinePostsRequest;
use App\Http\Requests\Account\GetPopularRoutinePostsRequest;
use App\Http\Requests\Account\SearchAccountsRequest;
use App\Http\Requests\Like\GetAccountLikedRoutinePostsRequest;
use App\Http\Requests\Like\GetMyLikedRoutinePostsRequest;
use App\Http\Requests\PaginatedRequest;
use App\Http\Requests\Routine\GetCustomizedRoutinesRequest;
use App\Http\Requests\Routine\GetRoutineExecutionPostsRequest;
use App\Http\Requests\Routine\SearchRoutinesRequest;
use App\Http\Requests\RoutineExecution\GetAccountRoutineExecutionsRequest;
use App\Http\Requests\RoutineExecution\GetMyRoutineExecutionsRequest;
use App\Http\Requests\Support\GetMySupportsRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PaginatedRequestTest extends TestCase
{
    public function test_uses_forty_as_the_default_page_size_when_omitted(): void
    {
        self::assertSame(40, (new TestPaginatedRequest)->numberOfItemsPerPageForTest());
    }

    #[DataProvider('paginatedRequests')]
    public function test_uses_the_shared_pagination_contract(string $requestClass): void
    {
        $request = new $requestClass;

        self::assertInstanceOf(PaginatedRequest::class, $request);
        self::assertSame(
            ['sometimes', 'required', 'integer', 'min:1', 'max:120'],
            $request->rules()['number_of_items_per_page'],
        );
    }

    /**
     * @return array<string, array{class-string<PaginatedRequest>}>
     */
    public static function paginatedRequests(): array
    {
        return [
            'account posts' => [GetAccountRoutinePostsRequest::class],
            'favorite tag posts' => [GetFavoriteTagPostsRequest::class],
            'following posts' => [GetFollowingPostsRequest::class],
            'my posts' => [GetMyRoutinePostsRequest::class],
            'popular posts' => [GetPopularRoutinePostsRequest::class],
            'account search' => [SearchAccountsRequest::class],
            'account likes' => [GetAccountLikedRoutinePostsRequest::class],
            'my likes' => [GetMyLikedRoutinePostsRequest::class],
            'customized routines' => [GetCustomizedRoutinesRequest::class],
            'routine execution posts' => [GetRoutineExecutionPostsRequest::class],
            'routine search' => [SearchRoutinesRequest::class],
            'account routine executions' => [GetAccountRoutineExecutionsRequest::class],
            'my routine executions' => [GetMyRoutineExecutionsRequest::class],
            'my supports' => [GetMySupportsRequest::class],
        ];
    }
}

final class TestPaginatedRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function numberOfItemsPerPageForTest(): int
    {
        return $this->numberOfItemsPerPage();
    }
}
