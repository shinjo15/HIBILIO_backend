<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;

final class ValidRoutineVideoUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            new RoutineVideoUrl($value);
        } catch (\InvalidArgumentException) {
            $fail('動画URLは2048文字以下の有効なYouTube動画URLである必要があります。');
        }
    }
}
