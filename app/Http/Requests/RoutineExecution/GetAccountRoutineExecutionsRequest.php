<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use App\Http\Requests\PaginatedRequest;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInput;

final class GetAccountRoutineExecutionsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(?string $viewerAccountIdentifier = null): GetAccountRoutineExecutionsInput
    {
        return new GetAccountRoutineExecutionsInput((string) $this->route('account_identifier'), $this->page(), $this->numberOfItemsPerPage(), $viewerAccountIdentifier);
    }
}
