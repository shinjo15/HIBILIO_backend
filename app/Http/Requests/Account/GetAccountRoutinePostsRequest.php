<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\PaginatedRequest;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInput;

final class GetAccountRoutinePostsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(?string $viewerAccountIdentifier = null): GetAccountRoutinePostsInput
    {
        return new GetAccountRoutinePostsInput((string) $this->route('account_identifier'), $this->page(), $this->numberOfItemsPerPage(), $viewerAccountIdentifier);
    }
}
