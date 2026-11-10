<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use App\Http\Requests\PaginatedRequest;

final class GetRecentExecutedRoutinesRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function currentPage(): int
    {
        return $this->page();
    }

    public function perPage(): int
    {
        return $this->numberOfItemsPerPage();
    }
}
