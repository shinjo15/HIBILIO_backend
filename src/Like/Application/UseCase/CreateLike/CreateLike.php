<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\CreateLike;

use Src\Like\Domain\Exception\AlreadyLikedException;
use Src\Like\Domain\Exception\PostNotFoundForLikeException;
use Src\Like\Domain\Factory\LikeFactoryInterface;
use Src\Like\Domain\Factory\LikeNotificationFactoryInterface;
use Src\Like\Domain\Repository\LikeNotificationRepositoryInterface;
use Src\Like\Domain\Repository\LikeRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;

final readonly class CreateLike implements CreateLikeInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private PostRepositoryInterface $postRepository, private LikeRepositoryInterface $likeRepository, private LikeFactoryInterface $likeFactory, private LikeNotificationRepositoryInterface $notifications, private LikeNotificationFactoryInterface $notificationFactory) {}

    public function execute(CreateLikeInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $post = $this->postRepository->find($input->postIdentifier());
            if ($post === null) {
                throw new PostNotFoundForLikeException;
            }
            if ($this->likeRepository->exists($input->accountIdentifier(), $input->postIdentifier())) {
                throw new AlreadyLikedException;
            }
            $this->likeRepository->save($this->likeFactory->create($input->accountIdentifier(), $input->postIdentifier()));
            $this->postRepository->save($post->incrementLikeCount());
            $target = $this->notifications->findTarget($input->accountIdentifier(), $post->routineIdentifier());
            if ($target !== null && $target->notificationsEnabled()) {
                $notification = $this->notificationFactory->create($target->recipient(), $target->actorName(), $target->routineName());
                $this->notifications->save($notification);
            }
        });
    }
}
