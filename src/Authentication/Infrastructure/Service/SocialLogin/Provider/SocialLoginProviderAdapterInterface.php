<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Service\SocialLogin\Provider;

use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;

interface SocialLoginProviderAdapterInterface
{
    public function generate(SocialLoginTemporaryInfoSupport $temporaryInfo): string;

    /** @return null|array{provider_user_identifier: string, email_address: string} */
    public function retrieveProfile(string $authorizationCode, SocialLoginTemporaryInfoSupport $temporaryInfo): ?array;
}
