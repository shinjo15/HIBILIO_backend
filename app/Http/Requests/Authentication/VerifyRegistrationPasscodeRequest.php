<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeInput;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\RegistrationPasscodeChallengeIdentifier;

final class VerifyRegistrationPasscodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['passcode' => ['required', 'string', 'regex:/^\\d{6}$/D']];
    }

    public function messages(): array
    {
        return [
            'passcode.required' => '登録パスコードを入力してください。',
            'passcode.regex' => '登録パスコードは6桁の数字で入力してください。',
        ];
    }

    public function toInput(string $challengeIdentifier): VerifyRegistrationPasscodeInput
    {
        return new VerifyRegistrationPasscodeInput(
            new RegistrationPasscodeChallengeIdentifier($challengeIdentifier),
            new LoginPasscode($this->validated('passcode')),
        );
    }
}
