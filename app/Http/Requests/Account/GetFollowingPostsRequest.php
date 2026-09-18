<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\PaginatedRequest;
use Src\Account\Application\Usecase\Query\GetFollowingPosts\GetFollowingPostsInput;

final class GetFollowingPostsRequest extends PaginatedRequest
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

    public function toInput(string $accountIdentifier): GetFollowingPostsInput
    {
        return new GetFollowingPostsInput(
            accountIdentifier: $accountIdentifier,
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
        );
    }
}
