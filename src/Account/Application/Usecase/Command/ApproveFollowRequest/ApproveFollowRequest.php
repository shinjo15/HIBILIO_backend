<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\ApproveFollowRequest;

use Src\Account\Domain\Exception\BlockedFollowException;
use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Account\Domain\Factory\FollowFactoryInterface;
use Src\Account\Domain\Repository\BlockRepositoryInterface;
use Src\Account\Domain\Repository\FollowRepositoryInterface;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class ApproveFollowRequest implements ApproveFollowRequestInterface
{
    public function __construct(private TransactionManagerInterface $transactionManager, private FollowRequestRepositoryInterface $followRequestRepository, private FollowRepositoryInterface $followRepository, private FollowFactoryInterface $followFactory, private BlockRepositoryInterface $blockRepository) {}

    public function execute(ApproveFollowRequestInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $request = $this->followRequestRepository->findForUpdate($input->requestingAccountIdentifier(), $input->targetAccountIdentifier());
            if ($request === null || $request->status() !== FollowRequestStatus::PENDING) {
                throw new FollowRequestNotFoundException;
            }
            if ($this->blockRepository->find($input->requestingAccountIdentifier(), $input->targetAccountIdentifier()) !== null || $this->blockRepository->find($input->targetAccountIdentifier(), $input->requestingAccountIdentifier()) !== null) {
                throw new BlockedFollowException;
            }
            if ($this->followRepository->find($input->requestingAccountIdentifier(), $input->targetAccountIdentifier()) === null) {
                $this->followRepository->save($this->followFactory->create($input->requestingAccountIdentifier(), $input->targetAccountIdentifier()));
            }
            $request->approve();
            $this->followRequestRepository->save($request);
        });
    }
}
