<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

final readonly class AccountImageDimensions implements ValidationRule
{
    /** @param array<string> $acceptedMimes */
    public function __construct(
        private int $maximumWidth,
        private int $maximumHeight,
        private int $maximumPixels,
        private array $acceptedMimes = ['image/png', 'image/jpeg', 'image/webp'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $path = $value->getRealPath();
        $image = $path === false ? false : @getimagesize($path);

        if ($image === false || ! in_array($image['mime'], $this->acceptedMimes, true)) {
            $fail('画像形式を確認できません。');

            return;
        }

        $width = $image[0];
        $height = $image[1];

        if ($width > $this->maximumWidth || $height > $this->maximumHeight || $width * $height > $this->maximumPixels) {
            $fail("画像は{$this->maximumWidth}×{$this->maximumHeight}ピクセル、かつ{$this->maximumPixels}ピクセル以下である必要があります。");
        }
    }
}
