<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use App\Http\Requests\PaginatedRequest;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInput;

final class GetCustomizedRoutinesRequest extends PaginatedRequest
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

    public function toInput(?string $viewerAccountIdentifier = null): GetCustomizedRoutinesInput
    {
        return new GetCustomizedRoutinesInput(
            parentRoutineIdentifier: (string) $this->route('routine_identifier'),
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
            viewerAccountIdentifier: $viewerAccountIdentifier,
        );
    }
}
