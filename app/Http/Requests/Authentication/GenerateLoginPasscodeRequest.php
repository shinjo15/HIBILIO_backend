<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use Src\Authentication\Application\UseCase\GenerateLoginPasscode\GenerateLoginPasscodeInput;
use Src\Shared\Domain\ValueObject\EmailAddress;

final class GenerateLoginPasscodeRequest extends FormRequest
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

    public function toInput(): GenerateLoginPasscodeInput
    {
        return new GenerateLoginPasscodeInput(new EmailAddress($this->validated('email_address')));
    }
}
