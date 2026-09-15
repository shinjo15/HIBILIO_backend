<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Service;

use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;

interface SocialLoginStateServiceInterface
{
    public function store(SocialLoginTemporaryInfoSupport $temporaryInfo): void;

    public function consume(
        string $state,
        SocialLoginProvider $provider,
        string $browserSessionIdentifier,
    ): ?SocialLoginTemporaryInfoSupport;
}
