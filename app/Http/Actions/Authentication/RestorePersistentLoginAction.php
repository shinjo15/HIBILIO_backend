<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Support\PersistentLoginCookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken\AuthenticateWithPersistentLoginTokenInput;
use Src\Authentication\Application\UseCase\AuthenticateWithPersistentLoginToken\AuthenticateWithPersistentLoginTokenInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RestorePersistentLoginAction
{
    public function __construct(
        private AuthenticateWithPersistentLoginTokenInterface $authenticateWithPersistentLoginToken,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(Request $request): Response
    {
        if ($this->authService->accountIdentifier() !== null) {
            return new Response('', 204);
        }

        $cookie = $request->cookie('persistent_login');
        if (! is_string($cookie) || preg_match('/\A([a-f0-9]{32}):([a-f0-9]{64})\z/D', $cookie, $matches) !== 1) {
            return (new Response('', 401))->withCookie(PersistentLoginCookie::forget());
        }

        $output = $this->authenticateWithPersistentLoginToken->execute(
            new AuthenticateWithPersistentLoginTokenInput(new PersistentLoginSelector($matches[1]), $matches[2]),
        );
        if ($output->accountIdentifier() === null || $output->persistentLoginToken() === null) {
            return (new Response('', 401))->withCookie(PersistentLoginCookie::forget());
        }

        $this->authService->login($output->accountIdentifier());

        return (new Response('', 204))->withCookie(PersistentLoginCookie::make(
            $output->persistentLoginToken()->selector(),
            $output->persistentLoginToken()->rawValidator(),
            $output->persistentLoginToken()->expiresAt(),
        ));
    }
}
