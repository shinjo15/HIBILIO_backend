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
            'provider' => [
                'required',
                'string',
                Rule::in([SocialLoginProvider::GOOGLE->value, SocialLoginProvider::APPLE->value]),
            ],
        ];
    }

    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['provider' => $this->route('provider')]);
    }

    public function toInput(string $browserSessionIdentifier): StartSocialLoginInput
    {
        return new StartSocialLoginInput(
            SocialLoginProvider::from($this->validated('provider')),
            $browserSessionIdentifier,
        );
    }
}
