<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;

interface SocialLoginServiceInterface
{
    public function start(
        SocialLoginProvider $provider,
        ?string $accountName,
        string $browserSessionIdentifier,
    ): string;

    /**
     * @return null|array{
     *     provider_user_identifier: string,
     *     email_address: string,
     *     account_name: ?string
     * }
     */
    public function authenticate(
        SocialLoginProvider $provider,
        string $authorizationCode,
        string $state,
        string $browserSessionIdentifier,
    ): ?array;

    public function discard(
        SocialLoginProvider $provider,
        string $state,
        string $browserSessionIdentifier,
    ): void;
}
