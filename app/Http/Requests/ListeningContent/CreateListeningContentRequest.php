<?php

declare(strict_types=1);

namespace App\Http\Requests\ListeningContent;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Src\ListeningContent\Application\UseCase\CreateListeningContent\CreateListeningContentInput;
use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateListeningContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', static function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('コンテンツのURLは有効な形式である必要があります。');
                }
            }],
        ];
    }

    public function toInput(string $accountIdentifier): CreateListeningContentInput
    {
        return new CreateListeningContentInput(
            new AccountIdentifier($accountIdentifier),
            new ListeningContentUrl($this->validated('url')),
        );
    }
}
