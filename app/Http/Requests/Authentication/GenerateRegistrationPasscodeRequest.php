<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInput;

final class GenerateRegistrationPasscodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email_address' => ['required', 'string', 'email']];
    }

    public function messages(): array
    {
        return [
            'email_address.required' => 'メールアドレスを入力してください。',
            'email_address.email' => 'メールアドレスは有効な形式で入力してください。',
        ];
    }

    public function toInput(): GenerateRegistrationPasscodeInput
    {
        return new GenerateRegistrationPasscodeInput(new EmailAddress($this->validated('email_address')));
    }
}
