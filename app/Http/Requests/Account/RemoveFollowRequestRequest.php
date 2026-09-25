<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\RemoveFollowRequest\RemoveFollowRequestInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class RemoveFollowRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $requestingAccountIdentifier): RemoveFollowRequestInput
    {
        return new RemoveFollowRequestInput(
            requestingAccountIdentifier: new AccountIdentifier($requestingAccountIdentifier),
            targetAccountIdentifier: new AccountIdentifier((string) $this->route('target_account_identifier')),
        );
    }
}
