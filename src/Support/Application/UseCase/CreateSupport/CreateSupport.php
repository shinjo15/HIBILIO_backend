<?php

declare(strict_types=1);

namespace Src\Support\Application\UseCase\CreateSupport;

use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Src\Support\Domain\Exception\AlreadySupportedException;
use Src\Support\Domain\Exception\PostNotFoundForSupportException;
use Src\Support\Domain\Factory\SupportFactoryInterface;
use Src\Support\Domain\Factory\SupportNotificationFactoryInterface;
use Src\Support\Domain\Repository\SupportNotificationRepositoryInterface;
use Src\Support\Domain\Repository\SupportRepositoryInterface;

final readonly class CreateSupport implements CreateSupportInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private PostRepositoryInterface $postRepository, private SupportRepositoryInterface $supportRepository, private SupportFactoryInterface $supportFactory, private SupportNotificationRepositoryInterface $notifications, private SupportNotificationFactoryInterface $notificationFactory) {}

    public function execute(CreateSupportInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $post = $this->postRepository->find($input->postIdentifier());
            if ($post === null) {
                throw new PostNotFoundForSupportException;
            }
            if ($this->supportRepository->exists($input->accountIdentifier(), $input->postIdentifier())) {
                throw new AlreadySupportedException;
            }
            $this->supportRepository->save($this->supportFactory->create($input->accountIdentifier(), $input->postIdentifier()));
            $this->postRepository->save($post->incrementSupportCount());
            $target = $this->notifications->findTarget($input->accountIdentifier(), $input->postIdentifier());
            if ($target !== null && $target->notificationsEnabled()) {
                $notification = $this->notificationFactory->create($target->recipient(), $target->actorName(), $target->routineName());
                $this->notifications->save($notification);
            }
        });
    }
}
