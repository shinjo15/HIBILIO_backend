<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Http\Requests\PaginatedRequest;
use Src\Support\Application\Usecase\Query\GetMySupports\GetMySupportsInput;

final class GetMySupportsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(string $accountIdentifier): GetMySupportsInput
    {
        return new GetMySupportsInput(
            accountIdentifier: $accountIdentifier,
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
        );
    }
}
