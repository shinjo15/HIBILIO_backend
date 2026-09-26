<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\VerifyLoginPasscodeRequest;
use App\Http\Support\PersistentLoginCookie;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Authentication\Application\Service\PasscodeSessionServiceInterface;
use Src\Authentication\Application\UseCase\VerifyLoginPasscode\VerifyLoginPasscodeInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

final readonly class VerifyLoginPasscodeAction
{
    public function __construct(
        private VerifyLoginPasscodeInterface $verifyLoginPasscode,
        private PasscodeSessionServiceInterface $passcodeSessionService,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(VerifyLoginPasscodeRequest $request): Response
    {
        try {
            $challengeIdentifier = $this->passcodeSessionService->challengeIdentifier();
        } catch (RuntimeException) {
            return new Response('', 401);
        }

        try {
            $output = $this->verifyLoginPasscode->execute($request->toInput($challengeIdentifier));
        } catch (Throwable) {
            return new Response('', 401);
        }
        if ($output->accountIdentifier() === null || $output->persistentLoginToken() === null) {
            return new Response('', 401);
        }

        $this->passcodeSessionService->clearChallengeIdentifier();
        $this->authService->login($output->accountIdentifier());

        return (new Response('', 204))->withCookie(PersistentLoginCookie::make(
            $output->persistentLoginToken()->selector(),
            $output->persistentLoginToken()->rawValidator(),
            $output->persistentLoginToken()->expiresAt(),
        ));
    }
}
