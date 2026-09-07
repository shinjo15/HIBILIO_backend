<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\VerifyRegistrationPasscodeRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;
use Src\Authentication\Application\UseCase\VerifyRegistrationPasscode\VerifyRegistrationPasscodeInterface;
use Throwable;

final readonly class VerifyRegistrationPasscodeAction
{
    public function __construct(
        private VerifyRegistrationPasscodeInterface $verifyRegistrationPasscode,
        private RegistrationPasscodeSessionServiceInterface $registrationPasscodeSessionService,
    ) {}

    public function __invoke(VerifyRegistrationPasscodeRequest $request): Response
    {
        try {
            $challengeIdentifier = $this->registrationPasscodeSessionService->challengeIdentifier();
        } catch (RuntimeException) {
            return new Response('', 401);
        }

        try {
            $output = $this->verifyRegistrationPasscode->execute($request->toInput($challengeIdentifier));
        } catch (Throwable) {
            return new Response('', 401);
        }

        if ($output->emailAddress() === null) {
            return new Response('', 401);
        }

        $this->registrationPasscodeSessionService->clearChallengeIdentifier();
        $this->registrationPasscodeSessionService->setVerifiedEmailAddress($output->emailAddress());

        return new Response('', 204);
    }
}
