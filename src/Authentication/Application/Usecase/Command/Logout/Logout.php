<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\Logout;

use Src\Authentication\Application\Service\PersistentLoginCookieServiceInterface;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class Logout implements LogoutInterface
{
    public function __construct(private PersistentLoginTokenRepositoryInterface $tokens, private PersistentLoginCookieServiceInterface $cookie, private AuthServiceInterface $authService) {}

    public function execute(LogoutInputPort $input): LogoutOutputPort
    {
        $value = $this->cookie->value();
        $selector = $value === null ? null : explode('.', $value, 2)[0];
        if (is_string($selector) && $selector !== '') {
            $this->tokens->delete(new PersistentLoginSelector($selector));
        }
        $this->authService->logout();
        $this->cookie->clear();

        return new LogoutOutput;
    }
}
