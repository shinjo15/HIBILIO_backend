<?php

declare(strict_types=1);

namespace Src\Account\Domain\ValueObject;

use InvalidArgumentException;

final readonly class AccountIcon
{
    public function __construct(
        private string $contents,
    ) {
        $image = getimagesizefromstring($contents);

        if ($image === false || $image['mime'] !== 'image/webp') {
            throw new InvalidArgumentException('アイコン画像はWebP形式である必要があります。');
        }

        if (
            $image[0] < 128
            || $image[1] < 128
            || $image[0] > 1024
            || $image[1] > 1024
            || $image[0] !== $image[1]
        ) {
            throw new InvalidArgumentException('アイコン画像は128〜1024ピクセルの正方形である必要があります。');
        }

        if (strlen($contents) > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('アイコン画像は5MB以下である必要があります。');
        }
    }

    public function contents(): string
    {
        return $this->contents;
    }
}
