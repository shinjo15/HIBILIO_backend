<?php

declare(strict_types=1);

namespace Src\Notification\Application\Usecase\Command\MarkPersonalNotificationAsRead;

use Src\Notification\Domain\Exception\PersonalNotificationNotFoundException;
use Src\Notification\Domain\Repository\PersonalNotificationRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class MarkPersonalNotificationAsRead implements MarkPersonalNotificationAsReadInterface
{
    public function __construct(private PersonalNotificationRepositoryInterface $notificationRepository, private TransactionManagerInterface $transactionManager) {}

    public function execute(MarkPersonalNotificationAsReadInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $notification = $this->notificationRepository->find($input->notificationIdentifier());
            if ($notification === null) {
                throw new PersonalNotificationNotFoundException;
            }

            $notification->markAsReadBy($input->accountIdentifier());
            $this->notificationRepository->save($notification);
        });
    }
}
