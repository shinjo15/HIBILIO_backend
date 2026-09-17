<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use App\Http\Requests\PaginatedRequest;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInput;

final class GetMyRoutineExecutionsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(string $accountIdentifier): GetAccountRoutineExecutionsInput
    {
        return new GetAccountRoutineExecutionsInput($accountIdentifier, $this->page(), $this->numberOfItemsPerPage(), $accountIdentifier);
    }
}
