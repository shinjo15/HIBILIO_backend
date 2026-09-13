<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Authentication\Application\Usecase\Command\StartSocialLogin\StartSocialLoginInput;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

final class StartSocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in([SocialLoginProvider::GOOGLE->value, SocialLoginProvider::APPLE->value])],
            'account_name' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['provider' => $this->route('provider')]);
    }

    public function toInput(string $browserSessionIdentifier): StartSocialLoginInput
    {
        $accountName = $this->validated('account_name');

        return new StartSocialLoginInput(
            SocialLoginProvider::from($this->validated('provider')),
            is_string($accountName) && trim($accountName) !== '' ? $accountName : null,
            $browserSessionIdentifier,
        );
    }
}
