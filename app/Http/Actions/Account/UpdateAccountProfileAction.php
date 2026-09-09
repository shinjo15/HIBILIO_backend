<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\UpdateAccountProfileRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Service\AccountImageConverterServiceInterface;
use Src\Account\Application\Usecase\Command\UpdateAccountProfile\UpdateAccountProfileInterface;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class UpdateAccountProfileAction
{
    public function __construct(
        private UpdateAccountProfileInterface $updateAccountProfile,
        private AccountImageConverterServiceInterface $accountImageConverter,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(UpdateAccountProfileRequest $request): Response
    {
        try {
            $accountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            return new Response('', 401);
        }

        try {
            $this->updateAccountProfile->execute($request->toInput(
                $accountIdentifier,
                $request->iconImageContents() === null ? null : $this->accountImageConverter->convertToIcon($request->iconImageContents()),
                $request->headerImageContents() === null ? null : $this->accountImageConverter->convertToHeader($request->headerImageContents()),
            ));
        } catch (AccountNotFoundException) {
            return new Response('', 404);
        }

        return new Response('', 204);
    }
}
