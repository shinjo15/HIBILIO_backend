<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Account\Application\Usecase\Command\ChangeAccountVisibility\ChangeAccountVisibilityInput;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ChangeAccountVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['visibility' => ['required', Rule::enum(AccountVisibility::class)]];
    }

    public function toInput(string $accountIdentifier): ChangeAccountVisibilityInput
    {
        return new ChangeAccountVisibilityInput(new AccountIdentifier($accountIdentifier), AccountVisibility::from($this->validated('visibility')));
    }
}
