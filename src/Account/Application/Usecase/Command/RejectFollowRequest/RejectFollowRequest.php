<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RejectFollowRequest;

use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class RejectFollowRequest implements RejectFollowRequestInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private FollowRequestRepositoryInterface $followRequestRepository) {}

    public function execute(RejectFollowRequestInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $request = $this->followRequestRepository->findForUpdate($input->requestingAccountIdentifier(), $input->targetAccountIdentifier());
            if ($request === null || $request->status() !== FollowRequestStatus::PENDING) {
                throw new FollowRequestNotFoundException;
            }
            $request->reject();
            $this->followRequestRepository->save($request);
        });
    }
}
