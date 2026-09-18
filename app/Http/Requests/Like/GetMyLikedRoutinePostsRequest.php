<?php

declare(strict_types=1);

namespace App\Http\Requests\Like;

use App\Http\Requests\PaginatedRequest;
use Src\Like\Application\Usecase\Query\GetLikedRoutinePosts\GetLikedRoutinePostsInput;

final class GetMyLikedRoutinePostsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(string $accountIdentifier): GetLikedRoutinePostsInput
    {
        return new GetLikedRoutinePostsInput(
            accountIdentifier: $accountIdentifier,
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
            viewerAccountIdentifier: $accountIdentifier,
        );
    }
}
