<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\PaginatedRequest;
use Src\Account\Application\Usecase\Query\GetPopularRoutinePosts\GetPopularRoutinePostsInput;

final class GetPopularRoutinePostsRequest extends PaginatedRequest
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

    public function toInput(?string $accountIdentifier): GetPopularRoutinePostsInput
    {
        return new GetPopularRoutinePostsInput(
            accountIdentifier: $accountIdentifier,
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
        );
    }
}
