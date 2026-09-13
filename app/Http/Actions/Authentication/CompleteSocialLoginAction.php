<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\CompleteSocialLoginRequest;
use Illuminate\Http\Response;
use Src\Authentication\Application\Service\SocialLoginServiceInterface;
use Src\Authentication\Application\Usecase\Command\CompleteSocialLogin\CompleteSocialLoginInterface;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

final readonly class CompleteSocialLoginAction
{
    public function __construct(
        private CompleteSocialLoginInterface $completeSocialLogin,
        private SocialLoginServiceInterface $socialLoginService,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(CompleteSocialLoginRequest $request): Response
    {
        $provider = $request->validated('provider');
        $state = $request->validated('state');
        if ($request->wasDenied()) {
            $this->discard($provider, $state, $request->session()->getId());

            return new Response('', 401);
        }

        try {
            $output = $this->completeSocialLogin->execute($request->toInput($request->session()->getId()));
            if (! $output->isAuthenticated() || $output->accountIdentifier() === null) {
                return new Response('', 401);
            }
            $this->authService->login($output->accountIdentifier());

            return new Response('', 204);
        } catch (Throwable $exception) {
            report($exception);

            return new Response('', 401);
        }
    }

    private function discard(string $provider, string $state, string $sessionIdentifier): void
    {
        try {
            $this->socialLoginService->discard(
                SocialLoginProvider::from($provider),
                $state,
                $sessionIdentifier,
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
