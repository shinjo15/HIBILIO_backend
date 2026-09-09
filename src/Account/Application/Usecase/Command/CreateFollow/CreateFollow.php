<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\CreateFollow;

use Src\Account\Domain\Exception\DuplicateFollowException;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Factory\FollowFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\Repository\FollowRepositoryInterface;
use Src\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class CreateFollow implements CreateFollowInterface
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private AccountRepositoryInterface $accountRepository,
        private FollowRepositoryInterface $followRepository,
        private FollowFactoryInterface $followFactory,
    ) {}

    public function execute(CreateFollowInputPort $input): void
    {
        $this->transactionManager->transaction(function () use ($input): void {
            if ($this->accountRepository->find($input->followedAccountIdentifier()) === null) {
                throw new AccountNotFoundException;
            }

            if ($this->followRepository->find(
                $input->followingAccountIdentifier(),
                $input->followedAccountIdentifier(),
            ) !== null) {
                throw new DuplicateFollowException;
            }

            $this->followRepository->save($this->followFactory->create(
                followingAccountIdentifier: $input->followingAccountIdentifier(),
                followedAccountIdentifier: $input->followedAccountIdentifier(),
            ));
        });
    }
}
