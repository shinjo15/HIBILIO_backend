<?php

declare(strict_types=1);

namespace App\Http\Requests\Like;

use Illuminate\Foundation\Http\FormRequest;
use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\PostIdentifier;

final class RemoveLikeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $accountIdentifier): RemoveLikeInput
    {
        return new RemoveLikeInput(new AccountIdentifier($accountIdentifier), new PostIdentifier((string) $this->route('post_identifier')));
    }
}
