<?php

declare(strict_types=1);

namespace App\Http\Requests\Like;

use App\Http\Requests\PaginatedRequest;
use Src\Like\Application\Usecase\Query\GetLikedRoutinePosts\GetLikedRoutinePostsInput;

final class GetAccountLikedRoutinePostsRequest extends PaginatedRequest
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

    public function toInput(?string $viewerAccountIdentifier = null): GetLikedRoutinePostsInput
    {
        return new GetLikedRoutinePostsInput(
            accountIdentifier: (string) $this->route('account_identifier'),
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
            viewerAccountIdentifier: $viewerAccountIdentifier,
        );
    }
}
