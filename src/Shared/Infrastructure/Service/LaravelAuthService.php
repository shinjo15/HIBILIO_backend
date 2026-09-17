<?php

declare(strict_types=1);

namespace Src\Shared\Infrastructure\Service;

use Illuminate\Http\Request;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class LaravelAuthService implements AuthServiceInterface
{
    public const SESSION_KEY = 'account_identifier';

    public function __construct(
        private Request $request,
    ) {}

    public function login(AccountIdentifier $accountIdentifier): void
    {
        $this->request->session()->regenerate();
        $this->request->session()->put(self::SESSION_KEY, $accountIdentifier->value());
    }

    public function accountIdentifier(): ?string
    {
        $accountIdentifier = $this->request->session()->get(self::SESSION_KEY);

        if (! is_string($accountIdentifier) || $accountIdentifier === '') {
            return null;
        }

        return $accountIdentifier;
    }
}
