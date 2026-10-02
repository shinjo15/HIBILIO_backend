<?php

declare(strict_types=1);

namespace Src\Contact\Application\UseCase\SendContactSupportMail;

use DateTimeImmutable;
use Src\Contact\Application\Service\ContactSupportMailServiceInterface;
use Src\Contact\Domain\Exception\ContactAccountNotFoundException;
use Src\Contact\Domain\Factory\ContactFactoryInterface;
use Src\Contact\Domain\Repository\AccountRepositoryInterface;
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
        $emailAddress = $this->accountRepository->findEmailAddress($input->accountIdentifier());
        if ($emailAddress === null) {
            throw new ContactAccountNotFoundException;
        }

        $contact = $this->contactFactory->create($input->accountIdentifier(), $input->title(), $input->content());

        try {
            $this->mailService->send($contact, $emailAddress);
        } catch (\Throwable $exception) {
            $contact->markFailed();
            $this->contactRepository->save($contact);

            throw $exception;
        }

        $contact->markSent(new DateTimeImmutable);
        $this->contactRepository->save($contact);
    }
}
