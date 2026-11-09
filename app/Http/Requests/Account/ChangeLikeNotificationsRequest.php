<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\ChangeLikeNotifications\ChangeLikeNotificationsInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ChangeLikeNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['like_notifications_enabled' => ['required', 'boolean:strict']];
    }

    public function toInput(string $accountIdentifier): ChangeLikeNotificationsInput
    {
        return new ChangeLikeNotificationsInput(new AccountIdentifier($accountIdentifier), $this->validated('like_notifications_enabled'));
    }
}
