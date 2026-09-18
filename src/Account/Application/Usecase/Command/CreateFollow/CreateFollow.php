<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateFollow;

use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Exception\DuplicateFollowException;
use Src\Account\Domain\Factory\FollowFactoryInterface;
use Src\Account\Domain\Factory\FollowRequestFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\Repository\FollowRepositoryInterface;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateFollow implements CreateFollowInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private AccountRepositoryInterface $accountRepository,
        private FollowRepositoryInterface $followRepository,
        private FollowFactoryInterface $followFactory,
        private FollowRequestRepositoryInterface $followRequestRepository,
        private FollowRequestFactoryInterface $followRequestFactory,
    ) {}

    public function execute(CreateFollowInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            $followedAccount = $this->accountRepository->find($input->followedAccountIdentifier());
            if ($followedAccount === null) {
                throw new AccountNotFoundException;
            }

            if ($this->followRepository->find(
                $input->followingAccountIdentifier(),
                $input->followedAccountIdentifier(),
            ) !== null) {
                throw new DuplicateFollowException;
            }

            if ($followedAccount->visibility() === AccountVisibility::PRIVATE) {
                $followRequest = $this->followRequestRepository->find($input->followingAccountIdentifier(), $input->followedAccountIdentifier());
                if ($followRequest === null) {
                    $followRequest = $this->followRequestFactory->create($input->followingAccountIdentifier(), $input->followedAccountIdentifier());
                } elseif ($followRequest->status() === FollowRequestStatus::REJECTED) {
                    $followRequest->requestAgain();
                } else {
                    throw new DuplicateFollowException;
                }
                $this->followRequestRepository->save($followRequest);

                return;
            }

            $this->followRepository->save($this->followFactory->create(
                followingAccountIdentifier: $input->followingAccountIdentifier(),
                followedAccountIdentifier: $input->followedAccountIdentifier(),
            ));
        });
    }
}
