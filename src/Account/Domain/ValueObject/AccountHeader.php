<?php

declare(strict_types=1);

namespace Src\Account\Domain\ValueObject;

use InvalidArgumentException;

final readonly class AccountHeader
{
    public function __construct(
        private string $contents,
    ) {
        $image = getimagesizefromstring($contents);

        if ($image === false || $image['mime'] !== 'image/webp') {
            throw new InvalidArgumentException('ヘッダー画像はWebP形式である必要があります。');
        }

        if (
            $image[0] < 640
            || $image[1] < 320
            || $image[0] > 1920
            || $image[1] > 1080
        ) {
            throw new InvalidArgumentException('ヘッダー画像は640×320〜1920×1080ピクセルである必要があります。');
        }

        if (strlen($contents) > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('ヘッダー画像は10MB以下である必要があります。');
        }
    }

    public function contents(): string
    {
        return $this->contents;
    }
}
