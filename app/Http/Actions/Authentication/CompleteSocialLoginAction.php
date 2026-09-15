<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\CompleteSocialLoginRequest;
use Illuminate\Http\RedirectResponse;
use Src\Authentication\Application\Service\PendingSocialRegistrationSessionServiceInterface;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInterface;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

final readonly class CompleteSocialLoginAction
{
    public function __construct(
        private CompleteSocialLoginInterface $completeSocialLogin,
        private PendingSocialRegistrationSessionServiceInterface $pendingRegistrationSession,
        private SocialLoginServiceInterface $socialLoginService,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(CompleteSocialLoginRequest $request): RedirectResponse
    {
        $provider = SocialLoginProvider::from($request->validated('provider'));
        $state = $request->validated('state');
        $sessionIdentifier = $request->session()->getId();

        if ($request->wasDenied()) {
            $this->discard($provider, $state, $sessionIdentifier);

            return $this->failureRedirect();
        }

        try {
            $output = $this->completeSocialLogin->execute($request->toInput($sessionIdentifier));
            if ($output->isAuthenticated() && $output->accountIdentifier() !== null) {
                $this->pendingRegistrationSession->clear();
                $this->authService->login($output->accountIdentifier());

                return $this->homeRedirect();
            }

            if ($output->pendingRegistration() !== null) {
                $this->pendingRegistrationSession->set($output->pendingRegistration());

                return $this->registerRedirect();
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $this->failureRedirect();
    }

    private function discard(
        SocialLoginProvider $provider,
        string $state,
        string $sessionIdentifier,
    ): void {
        try {
            $this->socialLoginService->discard($provider, $state, $sessionIdentifier);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function homeRedirect(): RedirectResponse
    {
        return redirect()->away($this->frontendUrl().'/');
    }

    private function registerRedirect(): RedirectResponse
    {
        return redirect()->away($this->frontendUrl().'/sign-up?social_registration=1');
    }

    private function failureRedirect(): RedirectResponse
    {
        return redirect()->away($this->frontendUrl().'/login?social_login=failed');
    }

    private function frontendUrl(): string
    {
        $frontendUrl = config('services.frontend_url');
        if (
            ! is_string($frontendUrl)
            || trim($frontendUrl) === ''
            || ! $this->isSafeFrontendUrl($frontendUrl)
        ) {
            return rtrim(url('/'), '/');
        }

        return rtrim($frontendUrl, '/');
    }

    private function isSafeFrontendUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            && is_string($parts['host'] ?? null)
            && ($parts['user'] ?? null) === null
            && ($parts['pass'] ?? null) === null
            && ($parts['query'] ?? null) === null
            && ($parts['fragment'] ?? null) === null;
    }
}
