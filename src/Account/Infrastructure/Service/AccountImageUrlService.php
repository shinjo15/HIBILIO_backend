<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Service;

use Illuminate\Support\Facades\Storage;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class AccountImageUrlService implements AccountImageUrlServiceInterface
{
    public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
    {
        return $this->imageUrl($accountIdentifier, 'icon/icon.webp');
    }

    public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
    {
        return $this->imageUrl($accountIdentifier, 'header/header.webp');
    }

    private function imageUrl(AccountIdentifier $accountIdentifier, string $filename): ?string
    {
        $disk = Storage::disk(config('account.images.storage') === 's3' ? 's3' : 'account_images');
        $path = "accounts/{$accountIdentifier->value()}/{$filename}";

        if (! $disk->exists($path)) {
            return null;
        }

        return $disk->temporaryUrl($path, now()->addHour());
    }
}
