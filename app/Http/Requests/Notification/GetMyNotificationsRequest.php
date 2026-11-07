<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use App\Http\Requests\PaginatedRequest;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

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
            new AccountIdentifier($accountIdentifier),
            $this->page(),
            $this->numberOfItemsPerPage(),
        );
    }
}
