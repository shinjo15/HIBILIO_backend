<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin;

use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Service\SocialLoginStateServiceInterface;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\SocialLoginTemporaryInfoService;

final readonly class SocialLoginService implements SocialLoginServiceInterface
{
    public function __construct(
        private SocialLoginTemporaryInfoService $temporaryInfoService,
        private SocialLoginStateServiceInterface $stateService,
        private SocialLoginProviderService $providerService,
    ) {}

    public function start(SocialLoginProvider $provider, ?string $accountName, string $browserSessionIdentifier): string
    {
        $temporaryInfo = $this->temporaryInfoService->generate($provider, $accountName, $browserSessionIdentifier);
        $authorizationUrl = $this->providerService->generateAuthorizationUrl($temporaryInfo);
        $this->stateService->store($temporaryInfo);

        return $authorizationUrl;
    }

    /** @return null|array{provider_user_identifier: string, email_address: string, account_name: ?string} */
    public function authenticate(SocialLoginProvider $provider, string $authorizationCode, string $state, string $browserSessionIdentifier): ?array
    {
        $temporaryInfo = $this->stateService->consume($state, $provider, $browserSessionIdentifier);
        if ($temporaryInfo === null) {
            return null;
        }
        $profile = $this->providerService->retrieveProfile($authorizationCode, $temporaryInfo);
        if ($profile === null) {
            return null;
        }

        return [...$profile, 'account_name' => $temporaryInfo->accountName()];
    }

    public function discard(SocialLoginProvider $provider, string $state, string $browserSessionIdentifier): void
    {
        $this->stateService->consume($state, $provider, $browserSessionIdentifier);
    }
}
