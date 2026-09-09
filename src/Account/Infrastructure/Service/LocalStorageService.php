<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Service;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Src\Account\Application\Service\StorageServiceInterface;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LocalStorageService implements StorageServiceInterface
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
        $written = Storage::disk('account_images')->put(
            "accounts/{$accountIdentifier->value()}/{$filename}",
            $contents,
        );

        if (! $written) {
            throw new RuntimeException('画像をローカルストレージへ保存できません。');
        }
    }

    private function delete(AccountIdentifier $accountIdentifier, string $filename): void
    {
        Storage::disk('account_images')->delete("accounts/{$accountIdentifier->value()}/{$filename}");
    }
}
