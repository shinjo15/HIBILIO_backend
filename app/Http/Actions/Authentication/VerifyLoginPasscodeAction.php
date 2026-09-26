<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\VerifyLoginPasscodeRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Authentication\Application\Service\PasscodeSessionServiceInterface;
use Src\Authentication\Application\UseCase\VerifyLoginPasscode\VerifyLoginPasscodeInterface;
use Throwable;

final readonly class VerifyLoginPasscodeAction
{
    public function __construct(
        private VerifyLoginPasscodeInterface $verifyLoginPasscode,
        private PasscodeSessionServiceInterface $passcodeSessionService,
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
        if ($output->accountIdentifier() === null) {
            return new Response('', 401);
        }

        $this->passcodeSessionService->clearChallengeIdentifier();

        return new Response('', 204);
    }
}
