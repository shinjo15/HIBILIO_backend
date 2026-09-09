<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Service;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class S3StorageService implements StorageServiceInterface
{
    public function uploadIcon(AccountIdentifier $accountIdentifier, AccountIcon $icon): void
    {
        $this->upload($accountIdentifier, 'icon/icon.webp', $icon->contents());
    }

    public function uploadHeader(AccountIdentifier $accountIdentifier, AccountHeader $header): void
    {
        $this->upload($accountIdentifier, 'header/header.webp', $header->contents());
    }

    public function deleteIcon(AccountIdentifier $accountIdentifier): void
    {
        $this->delete($accountIdentifier, 'icon/icon.webp');
    }

    public function deleteHeader(AccountIdentifier $accountIdentifier): void
    {
        $this->delete($accountIdentifier, 'header/header.webp');
    }

    private function upload(AccountIdentifier $accountIdentifier, string $filename, string $contents): void
    {
        $written = Storage::disk('s3')->put(
            "accounts/{$accountIdentifier->value()}/{$filename}",
            $contents,
            ['visibility' => 'private'],
        );

        if (! $written) {
            throw new RuntimeException('画像をストレージへ保存できません。');
        }
    }

    private function delete(AccountIdentifier $accountIdentifier, string $filename): void
    {
        Storage::disk('s3')->delete("accounts/{$accountIdentifier->value()}/{$filename}");
    }
}
