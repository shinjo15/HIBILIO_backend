<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollowInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateFollowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'followed_account_identifier' => ['required', 'uuid'],
        ];
    }

    public function toInput(string $followingAccountIdentifier): CreateFollowInput
    {
        $validated = $this->validated();

        return new CreateFollowInput(
            followingAccountIdentifier: new AccountIdentifier($followingAccountIdentifier),
            followedAccountIdentifier: new AccountIdentifier($validated['followed_account_identifier']),
        );
    }
}
