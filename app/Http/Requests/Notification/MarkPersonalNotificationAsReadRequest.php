<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;
use Src\Notification\Application\Usecase\Command\MarkPersonalNotificationAsRead\MarkPersonalNotificationAsReadInput;
use Src\Notification\Domain\ValueObject\NotificationIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class MarkPersonalNotificationAsReadRequest extends FormRequest
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
        return [];
    }

    public function toInput(string $accountIdentifier): MarkPersonalNotificationAsReadInput
    {
        return new MarkPersonalNotificationAsReadInput(
            new AccountIdentifier($accountIdentifier),
            new NotificationIdentifier((string) $this->route('notification_identifier')),
        );
    }
}
