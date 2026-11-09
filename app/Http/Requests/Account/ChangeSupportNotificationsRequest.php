<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\ChangeSupportNotifications\ChangeSupportNotificationsInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ChangeSupportNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['support_notifications_enabled' => ['required', 'boolean:strict']];
    }

    public function toInput(string $accountIdentifier): ChangeSupportNotificationsInput
    {
        return new ChangeSupportNotificationsInput(new AccountIdentifier($accountIdentifier), $this->validated('support_notifications_enabled'));
    }
}
