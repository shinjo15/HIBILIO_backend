<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Service;

use InvalidArgumentException;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;

final class AccountImageConverterService implements AccountImageConverterServiceInterface
{
    public function convertToIcon(string $contents): AccountIcon
    {
        return new AccountIcon($this->convertToWebp($contents));
    }

    public function convertToHeader(string $contents): AccountHeader
    {
        return new AccountHeader($this->convertToWebp($contents));
    }

    private function convertToWebp(string $contents): string
    {
        try {
            $image = imagecreatefromstring($contents);
        } catch (\Throwable) {
            throw new InvalidArgumentException('画像をWebPへ変換できません。');
        }

        if ($image === false) {
            throw new InvalidArgumentException('画像をWebPへ変換できません。');
        }

        ob_start();
        $converted = imagewebp($image, null, 80);
        $webp = ob_get_clean();
        imagedestroy($image);

        if (! $converted || $webp === false) {
            throw new InvalidArgumentException('画像をWebPへ変換できません。');
        }

        return $webp;
    }
}
