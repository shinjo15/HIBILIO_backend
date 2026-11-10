<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\PaginatedRequest;
use Src\Account\Application\Usecase\Query\GetAccountPosts\GetAccountPostsInput;

final class GetAccountPostsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(?string $viewer): GetAccountPostsInput
    {
        return new GetAccountPostsInput((string) $this->route('account_identifier'), $this->page(), $this->numberOfItemsPerPage(), $viewer);
    }
}
