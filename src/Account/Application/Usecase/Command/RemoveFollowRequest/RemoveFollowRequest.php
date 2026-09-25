<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RemoveFollowRequest;

use Src\Account\Domain\Exception\FollowRequestCannotBeRemovedException;
use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class RemoveFollowRequest implements RemoveFollowRequestInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private FollowRequestRepositoryInterface $followRequestRepository) {}

    public function execute(RemoveFollowRequestInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $followRequest = $this->followRequestRepository->findForUpdate($input->requestingAccountIdentifier(), $input->targetAccountIdentifier());
            if ($followRequest === null) {
                throw new FollowRequestNotFoundException;
            }

            if ($followRequest->status() === FollowRequestStatus::APPROVED) {
                throw new FollowRequestCannotBeRemovedException;
            }

            $this->followRequestRepository->delete($followRequest);
        });
    }
}
