<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Http\Requests\PaginatedRequest;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInput;

final class GetMyNotificationsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|int>>
     */
    public function rules(): array
    {
        return $this->paginationRules();
    }

    public function toInput(string $accountIdentifier): GetMyNotificationsInput
    {
        return new GetMyNotificationsInput(
            $accountIdentifier,
            $this->page(),
            $this->numberOfItemsPerPage(),
        );
    }
}
