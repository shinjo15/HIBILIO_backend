<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\GenerateRegistrationPasscodeRequest;
use Illuminate\Http\Response;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInterface;

final readonly class GenerateRegistrationPasscodeAction
{
    public function __construct(
        private GenerateRegistrationPasscodeInterface $generateRegistrationPasscode,
        private RegistrationPasscodeSessionServiceInterface $registrationPasscodeSessionService,
    ) {}

    public function __invoke(GenerateRegistrationPasscodeRequest $request): Response
    {
        $this->registrationPasscodeSessionService->clearChallengeIdentifier();
        $this->registrationPasscodeSessionService->clearVerifiedEmailAddress();
        $output = $this->generateRegistrationPasscode->execute($request->toInput());

        if ($output->challengeIdentifier() !== null) {
            $this->registrationPasscodeSessionService->setChallengeIdentifier($output->challengeIdentifier());
        }

        return new Response('', 204);
    }
}
