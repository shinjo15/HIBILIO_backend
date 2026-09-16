<?php

declare(strict_types=1);

namespace Tests\Support;

use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

trait InteractsWithAccountImageUrlService
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->stubAccountImageUrlService();
    }

    protected function stubAccountImageUrlService(): void
    {
        $this->app->instance(AccountImageUrlServiceInterface::class, new class implements AccountImageUrlServiceInterface
        {
            public function iconImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return "https://images.example/accounts/{$accountIdentifier->value()}/icon";
            }

            public function headerImageUrl(AccountIdentifier $accountIdentifier): ?string
            {
                return "https://images.example/accounts/{$accountIdentifier->value()}/header";
            }
        });
    }
}
