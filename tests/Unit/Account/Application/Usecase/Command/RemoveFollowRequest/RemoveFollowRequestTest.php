<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Application\Usecase\Command\RemoveFollowRequest;

use PHPUnit\Framework\TestCase;
use Src\Account\Application\Usecase\Command\RemoveFollowRequest\RemoveFollowRequest;
use Src\Account\Application\Usecase\Command\RemoveFollowRequest\RemoveFollowRequestInput;
use Src\Account\Domain\Entity\FollowRequest;
use Src\Account\Domain\Exception\FollowRequestCannotBeRemovedException;
use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class RemoveFollowRequestTest extends TestCase
{
    public function test_deletes_a_pending_follow_request(): void
    {
        $repository = new InMemoryFollowRequestRepository($this->followRequest(FollowRequestStatus::PENDING));

        $this->useCase($repository)->execute($this->input());

        self::assertSame($repository->existing, $repository->deleted);
    }

    public function test_deletes_a_rejected_follow_request(): void
    {
        $repository = new InMemoryFollowRequestRepository($this->followRequest(FollowRequestStatus::REJECTED));

        $this->useCase($repository)->execute($this->input());

        self::assertSame($repository->existing, $repository->deleted);
    }

    public function test_rejects_an_approved_follow_request_without_deleting_it(): void
    {
        $repository = new InMemoryFollowRequestRepository($this->followRequest(FollowRequestStatus::APPROVED));

        $this->expectException(FollowRequestCannotBeRemovedException::class);

        try {
            $this->useCase($repository)->execute($this->input());
        } finally {
            self::assertNull($repository->deleted);
        }
    }

    public function test_rejects_a_missing_follow_request_without_deleting_it(): void
    {
        $repository = new InMemoryFollowRequestRepository(null);

        $this->expectException(FollowRequestNotFoundException::class);

        try {
            $this->useCase($repository)->execute($this->input());
        } finally {
            self::assertNull($repository->deleted);
        }
    }

    private function useCase(InMemoryFollowRequestRepository $repository): RemoveFollowRequest
    {
        return new RemoveFollowRequest(new ImmediateTransactionManager, $repository);
    }

    private function input(): RemoveFollowRequestInput
    {
        return new RemoveFollowRequestInput(
            new AccountIdentifier('11111111-1111-4111-8111-111111111111'),
            new AccountIdentifier('22222222-2222-4222-8222-222222222222'),
        );
    }

    private function followRequest(FollowRequestStatus $status): FollowRequest
    {
        return new FollowRequest(
            new AccountIdentifier('11111111-1111-4111-8111-111111111111'),
            new AccountIdentifier('22222222-2222-4222-8222-222222222222'),
            $status,
        );
    }
}

final class ImmediateTransactionManager implements TransactionManagerInterface
{
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
}

final class InMemoryFollowRequestRepository implements FollowRequestRepositoryInterface
{
    public ?FollowRequest $deleted = null;

    public function __construct(public ?FollowRequest $existing) {}

    public function find(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        return $this->matching($requestingAccountIdentifier, $targetAccountIdentifier);
    }

    public function findForUpdate(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        return $this->matching($requestingAccountIdentifier, $targetAccountIdentifier);
    }

    public function save(FollowRequest $followRequest): void {}

    public function delete(FollowRequest $followRequest): void
    {
        $this->deleted = $followRequest;
    }

    private function matching(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        if ($this->existing?->requestingAccountIdentifier()->value() !== $requestingAccountIdentifier->value() || $this->existing?->targetAccountIdentifier()->value() !== $targetAccountIdentifier->value()) {
            return null;
        }

        return $this->existing;
    }
}
