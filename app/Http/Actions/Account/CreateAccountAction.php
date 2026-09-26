<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\CreateAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Application\Usecase\Command\CreateAccount\CreateAccountInterface;
use Src\Account\Domain\Exception\DuplicateEmailAddressException;
use Src\Authentication\Application\Service\PendingSocialRegistrationSessionServiceInterface;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;

final readonly class CreateAccountAction
{
    public function __construct(
        private CreateAccountInterface $createAccount,
        private AccountImageConverterServiceInterface $accountImageConverter,
        private RegistrationPasscodeSessionServiceInterface $registrationPasscodeSessionService,
        private PendingSocialRegistrationSessionServiceInterface $pendingSocialRegistrationSession,

    ) {}

    public function __invoke(CreateAccountRequest $request): Response|JsonResponse
    {
        try {
            $pendingRegistration = $this->pendingSocialRegistrationSession->pending();
            $emailAddress = $pendingRegistration?->emailAddress()
                ?? $this->registrationPasscodeSessionService->verifiedEmailAddress();
            $icon = $request->iconImageContents();
            $header = $request->headerImageContents();

            $createAccountOutput = $this->createAccount->execute($request->toInput(
                $emailAddress,
                $icon === null ? null : $this->accountImageConverter->convertToIcon($icon),
                $header === null ? null : $this->accountImageConverter->convertToHeader($header),
                $pendingRegistration,
            ));

            $this->registrationPasscodeSessionService->clearVerifiedEmailAddress();
            $this->pendingSocialRegistrationSession->clear();

            return new Response('', 201);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (DuplicateEmailAddressException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        } catch (InvalidArgumentException) {
            return new Response('', 422);
        }
    }
}
