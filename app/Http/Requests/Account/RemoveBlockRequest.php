<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\RemoveBlock\RemoveBlockInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class RemoveBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $blockingAccountIdentifier): RemoveBlockInput
    {
        return new RemoveBlockInput(
            blockingAccountIdentifier: new AccountIdentifier($blockingAccountIdentifier),
            blockedAccountIdentifier: new AccountIdentifier((string) $this->route('blocked_account_identifier')),
        );
    }
}
