<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Http\Requests\PaginatedRequest;
use Src\Notification\Application\Usecase\Query\GetAnnouncements\GetAnnouncementsInput;

final class GetAnnouncementsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string|int>> */
    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(): GetAnnouncementsInput
    {
        return new GetAnnouncementsInput($this->page(), $this->numberOfItemsPerPage());
    }
}
