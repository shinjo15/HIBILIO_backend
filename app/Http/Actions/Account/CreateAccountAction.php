<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\CreateAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Application\UseCase\CreateAccount\CreateAccountInterface;
use Src\Account\Domain\Exception\DuplicateEmailAddressException;
use Src\Authentication\Application\Service\RegistrationPasscodeSessionServiceInterface;

final readonly class CreateAccountAction
{
    public function __construct(
        private CreateAccountInterface $createAccount,
        private AccountImageConverterServiceInterface $accountImageConverter,
        private RegistrationPasscodeSessionServiceInterface $registrationPasscodeSessionService,
    ) {}

    public function __invoke(CreateAccountRequest $request): Response|JsonResponse
    {
        try {
            $emailAddress = $this->registrationPasscodeSessionService->verifiedEmailAddress();
            $icon = $request->iconImageContents();
            $header = $request->headerImageContents();

            $this->createAccount->execute($request->toInput(
                $emailAddress,
                $icon === null ? null : $this->accountImageConverter->convertToIcon($icon),
                $header === null ? null : $this->accountImageConverter->convertToHeader($header),
            ));

            $this->registrationPasscodeSessionService->clearVerifiedEmailAddress();

            return new Response('', 201);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (DuplicateEmailAddressException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }
    }
}
