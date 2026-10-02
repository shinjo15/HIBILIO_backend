<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use DateTimeImmutable;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Contact\Application\Service\ContactSupportMailServiceInterface;
use Src\Contact\Domain\Exception\ContactAccountNotFoundException;
use Src\Contact\Domain\Factory\ContactFactoryInterface;
use Src\Contact\Domain\Repository\ContactRepositoryInterface;

final readonly class SendContactSupportMail implements SendContactSupportMailInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private ContactFactoryInterface $contactFactory,
        private ContactRepositoryInterface $contactRepository,
        private ContactSupportMailServiceInterface $mailService,
    ) {}

    public function execute(SendContactSupportMailInputPort $input): void
    {
        $account = $this->accountRepository->find($input->accountIdentifier());
        if ($account === null) {
            throw new ContactAccountNotFoundException;
        }

        $contact = $this->contactFactory->create($account->accountIdentifier(), $input->title(), $input->content());
        $this->contactRepository->save($contact);

        try {
            $this->mailService->send($contact, $account->emailAddress());
        } catch (\Throwable $exception) {
            $contact->markFailed();
            $this->contactRepository->save($contact);

            throw $exception;
        }

        $contact->markSent(new DateTimeImmutable);
        $this->contactRepository->save($contact);
    }
}
