<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\PaginatedRequest;
use Src\Account\Application\Usecase\Query\GetMyRoutines\GetMyRoutinesInput;

final class GetMyRoutinesRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(string $accountIdentifier): GetMyRoutinesInput
    {
        return new GetMyRoutinesInput($accountIdentifier, $this->page(), $this->numberOfItemsPerPage());
    }
}
