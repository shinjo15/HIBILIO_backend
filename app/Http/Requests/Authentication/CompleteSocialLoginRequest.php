<?php

declare(strict_types=1);

namespace App\Http\Requests\Authentication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RuntimeException;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInput;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

final class CompleteSocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in([SocialLoginProvider::GOOGLE->value, SocialLoginProvider::APPLE->value])],
            'code' => ['required_without:error', 'nullable', 'string', 'max:4096'],
            'state' => ['required', 'string', 'max:512'],
            'error' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validationData(): array
    {
        return array_merge(parent::validationData(), ['provider' => $this->route('provider')]);
    }

    public function wasDenied(): bool
    {
        return is_string($this->validated('error'));
    }

    public function toInput(string $browserSessionIdentifier): CompleteSocialLoginInput
    {
        $code = $this->validated('code');
        if (! is_string($code) || $code === '') {
            throw new RuntimeException('ソーシャルログインの認可コードがありません。');
        }

        return new CompleteSocialLoginInput(
            SocialLoginProvider::from($this->validated('provider')),
            $code,
            $this->validated('state'),
            $browserSessionIdentifier,
        );
    }
}
