<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\CreateBlock\CreateBlockInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blocked_account_identifier' => ['required', 'uuid'],
        ];
    }

    public function toInput(string $blockingAccountIdentifier): CreateBlockInput
    {
        $validated = $this->validated();

        return new CreateBlockInput(
            blockingAccountIdentifier: new AccountIdentifier($blockingAccountIdentifier),
            blockedAccountIdentifier: new AccountIdentifier($validated['blocked_account_identifier']),
        );
    }
}
