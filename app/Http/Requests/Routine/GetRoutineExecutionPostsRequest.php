<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use App\Http\Requests\PaginatedRequest;
use Src\Routine\Application\Usecase\Query\GetRoutineExecutionPosts\GetRoutineExecutionPostsInput;

final class GetRoutineExecutionPostsRequest extends PaginatedRequest
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

    public function toInput(?string $viewerAccountIdentifier = null): GetRoutineExecutionPostsInput
    {
        return new GetRoutineExecutionPostsInput(
            routineIdentifier: (string) $this->route('routine_identifier'),
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
            viewerAccountIdentifier: $viewerAccountIdentifier,
        );
    }
}
