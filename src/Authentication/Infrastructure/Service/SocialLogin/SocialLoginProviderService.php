<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\SocialLogin\Provider\AppleSocialLoginProviderAdapter;
use Src\Authentication\Infrastructure\Service\SocialLogin\Provider\GoogleSocialLoginProviderAdapter;
use Src\Authentication\Infrastructure\Service\SocialLogin\Provider\SocialLoginProviderAdapterInterface;

final readonly class SocialLoginProviderService
{
    public function __construct(
        private GoogleSocialLoginProviderAdapter $googleAdapter,
        private AppleSocialLoginProviderAdapter $appleAdapter,
    ) {}

    public function generateAuthorizationUrl(SocialLoginTemporaryInfoSupport $temporaryInfo): string
    {
        return $this->adapter($temporaryInfo->provider())->generate($temporaryInfo);
    }

    /** @return null|array{provider_user_identifier: string, email_address: string} */
    public function retrieveProfile(string $authorizationCode, SocialLoginTemporaryInfoSupport $temporaryInfo): ?array
    {
        return $this->adapter($temporaryInfo->provider())->retrieveProfile($authorizationCode, $temporaryInfo);
    }

    private function adapter(SocialLoginProvider $provider): SocialLoginProviderAdapterInterface
    {
        return match ($provider) {
            SocialLoginProvider::GOOGLE => $this->googleAdapter,
            SocialLoginProvider::APPLE => $this->appleAdapter,
        };
    }
}
