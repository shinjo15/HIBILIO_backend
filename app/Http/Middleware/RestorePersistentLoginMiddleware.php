<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Authentication\Application\Usecase\Command\Logout\LogoutInput;
use Src\Authentication\Application\Usecase\Command\Logout\LogoutInterface;
use Src\Authentication\Application\Usecase\Command\RestorePersistentLogin\RestorePersistentLoginInput;
use Src\Authentication\Application\Usecase\Command\RestorePersistentLogin\RestorePersistentLoginInterface;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateInput;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Symfony\Component\HttpFoundation\Response;

final readonly class RestorePersistentLoginMiddleware
{
    public function __construct(
        private AuthServiceInterface $authService,
        private GetAuthenticatedAccountStateInterface $authenticatedAccountState,
        private LogoutInterface $logout,
        private RestorePersistentLoginInterface $restorePersistentLogin,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier !== null) {
            if (! $this->authenticatedAccountState->execute(new GetAuthenticatedAccountStateInput(new AccountIdentifier($accountIdentifier)))->isAuthenticated()) {
                $this->logout->execute(new LogoutInput);
            }

            return $next($request);
        }
        $this->restorePersistentLogin->execute(new RestorePersistentLoginInput);

        return $next($request);
    }
}
