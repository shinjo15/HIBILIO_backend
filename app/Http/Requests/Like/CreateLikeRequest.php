<?php

declare(strict_types=1);

namespace App\Http\Requests\Like;

use Illuminate\Foundation\Http\FormRequest;
use Src\Like\Application\UseCase\CreateLike\CreateLikeInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

final class CreateLikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['post_identifier' => ['required', 'uuid']];
    }

    public function toInput(string $accountIdentifier): CreateLikeInput
    {
        return new CreateLikeInput(new AccountIdentifier($accountIdentifier), new PostIdentifier((string) $this->validated('post_identifier')));
    }
}
