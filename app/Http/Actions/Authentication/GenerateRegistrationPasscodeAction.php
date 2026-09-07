<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\GenerateRegistrationPasscodeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;
use Src\Authentication\Application\UseCase\GenerateRegistrationPasscode\GenerateRegistrationPasscodeInterface;
use Src\Authentication\Domain\Exception\RegistrationEmailAddressAlreadyRegisteredException;

final readonly class GenerateRegistrationPasscodeAction
{
    public function __construct(
        private GenerateRegistrationPasscodeInterface $generateRegistrationPasscode,
        private RegistrationPasscodeSessionServiceInterface $registrationPasscodeSessionService,
    ) {}

    public function __invoke(GenerateRegistrationPasscodeRequest $request): Response|JsonResponse
    {
        $this->registrationPasscodeSessionService->clearChallengeIdentifier();
        $this->registrationPasscodeSessionService->clearVerifiedEmailAddress();

        try {
            $output = $this->generateRegistrationPasscode->execute($request->toInput());
        } catch (RegistrationEmailAddressAlreadyRegisteredException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        if ($output->challengeIdentifier() !== null) {
            $this->registrationPasscodeSessionService->setChallengeIdentifier($output->challengeIdentifier());
        }

        return new Response('', 204);
    }
}
