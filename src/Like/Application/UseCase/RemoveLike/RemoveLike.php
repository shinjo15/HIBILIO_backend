<?php

declare(strict_types=1);

namespace Src\Like\Application\UseCase\RemoveLike;

use Src\Like\Domain\Exception\NotLikedException;
use Src\Like\Domain\Exception\PostNotFoundForLikeException;
use Src\Like\Domain\Repository\LikeRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;

final readonly class RemoveLike implements RemoveLikeInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private PostRepositoryInterface $postRepository, private LikeRepositoryInterface $likeRepository) {}

    public function execute(RemoveLikeInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $post = $this->postRepository->find($input->postIdentifier());
            if ($post === null) {
                throw new PostNotFoundForLikeException;
            }
            if (! $this->likeRepository->exists($input->accountIdentifier(), $input->postIdentifier())) {
                throw new NotLikedException;
            }
            $this->likeRepository->delete($input->accountIdentifier(), $input->postIdentifier());
            $this->postRepository->save($post->decrementLikeCount());
        });
    }
}
