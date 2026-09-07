<?php

declare(strict_types=1);

namespace Src\Support\Application\UseCase\RemoveSupport;

use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\Repository\PostRepositoryInterface;
use Src\Support\Domain\Exception\NotSupportedException;
use Src\Support\Domain\Exception\PostNotFoundForSupportException;
use Src\Support\Domain\Repository\SupportRepositoryInterface;

final readonly class RemoveSupport implements RemoveSupportInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private PostRepositoryInterface $postRepository, private SupportRepositoryInterface $supportRepository) {}

    public function execute(RemoveSupportInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $post = $this->postRepository->find($input->postIdentifier());
            if ($post === null) {
                throw new PostNotFoundForSupportException;
            }
            if (! $this->supportRepository->exists($input->accountIdentifier(), $input->postIdentifier())) {
                throw new NotSupportedException;
            }
            $this->supportRepository->delete($input->accountIdentifier(), $input->postIdentifier());
            $this->postRepository->save($post->decrementSupportCount());
        });
    }
}
