<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Account\Application\Usecase\Command\ChangeAccountUiMode\ChangeAccountUiModeInput;
use Src\Account\Domain\ValueObject\AccountUiMode;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ChangeAccountUiModeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['ui_mode' => ['required', Rule::enum(AccountUiMode::class)]];
    }

    public function toInput(string $accountIdentifier): ChangeAccountUiModeInput
    {
        return new ChangeAccountUiModeInput(new AccountIdentifier($accountIdentifier), AccountUiMode::from($this->validated('ui_mode')));
    }
}
