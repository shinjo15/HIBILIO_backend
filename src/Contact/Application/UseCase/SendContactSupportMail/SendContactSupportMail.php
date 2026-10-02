<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Contact\Application\Service\ContactSupportMailServiceInterface;
use Src\Contact\Domain\Exception\ContactAccountNotFoundException;

final readonly class SendContactSupportMail implements SendContactSupportMailInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private ContactSupportMailServiceInterface $mailService,
    ) {}

    public function execute(SendContactSupportMailInputPort $input): void
    {
        $account = $this->accountRepository->find($input->accountIdentifier());
        if ($account === null) {
            throw new ContactAccountNotFoundException;
        }

        $this->mailService->send($account->accountIdentifier(), $account->emailAddress(), $input->title(), $input->content());
    }
}
