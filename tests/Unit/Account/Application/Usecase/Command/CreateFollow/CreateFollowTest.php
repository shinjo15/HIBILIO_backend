<?php

declare(strict_types=1);

namespace Tests\Unit\Account\Application\Usecase\Command\CreateFollow;

use PHPUnit\Framework\TestCase;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollow;
use Src\Account\Application\Usecase\Command\CreateFollow\CreateFollowInput;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Entity\Follow;
use Src\Account\Domain\Entity\FollowRequest;
use Src\Account\Domain\Exception\AccountNotFoundException;
use Src\Account\Domain\Exception\DuplicateFollowException;
use Src\Account\Domain\Exception\SelfFollowException;
use Src\Account\Domain\Factory\FollowFactoryInterface;
use Src\Account\Domain\Factory\FollowRequestFactoryInterface;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\Repository\FollowRepositoryInterface;
use Src\Account\Domain\Repository\FollowRequestRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\FollowRequestStatus;
use Src\Shared\Application\Transaction\TransactionManagerInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class CreateFollowTest extends TestCase
{
    public function test_creates_a_follow_when_the_followed_account_exists(): void
    {
        $repository = new InMemoryFollowRepository;

        $this->useCase(true, $repository)->execute($this->input());

        self::assertCount(1, $repository->saved);
        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $repository->saved[0]->followingAccountIdentifier()->value());
        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $repository->saved[0]->followedAccountIdentifier()->value());
    }

    public function test_rejects_a_missing_followed_account(): void
    {
        $this->expectException(AccountNotFoundException::class);

        $this->useCase(false, new InMemoryFollowRepository)->execute($this->input());
    }

    public function test_rejects_a_duplicate_follow_in_the_same_direction(): void
    {
        $repository = new InMemoryFollowRepository;
        $repository->existing = $this->follow();

        $this->expectException(DuplicateFollowException::class);

        $this->useCase(true, $repository)->execute($this->input());
    }

    public function test_allows_following_in_the_opposite_direction(): void
    {
        $repository = new InMemoryFollowRepository;
        $repository->existing = (new TestFollowFactory)->create(
            followingAccountIdentifier: new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            followedAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
        );

        $this->useCase(true, $repository)->execute($this->input());

        self::assertCount(1, $repository->saved);
    }

    public function test_rejects_following_oneself(): void
    {
        $this->expectException(SelfFollowException::class);

        $this->useCase(true, new InMemoryFollowRepository)->execute(new CreateFollowInput(
            followingAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            followedAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
        ));
    }

    public function test_creates_a_pending_follow_request_for_a_private_account(): void
    {
        $followRepository = new InMemoryFollowRepository;
        $followRequestRepository = new InMemoryFollowRequestRepository;

        $this->useCase(true, $followRepository, true, $followRequestRepository)->execute($this->input());

        self::assertSame([], $followRepository->saved);
        self::assertCount(1, $followRequestRepository->saved);
        self::assertSame(FollowRequestStatus::PENDING, $followRequestRepository->saved[0]->status());
    }

    public function test_reopens_a_rejected_follow_request_for_a_private_account(): void
    {
        $followRequestRepository = new InMemoryFollowRequestRepository;
        $followRequestRepository->existing = new FollowRequest(
            new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
            FollowRequestStatus::REJECTED,
        );

        $this->useCase(true, new InMemoryFollowRepository, true, $followRequestRepository)->execute($this->input());

        self::assertSame(FollowRequestStatus::PENDING, $followRequestRepository->saved[0]->status());
    }

    private function useCase(bool $followedAccountExists, InMemoryFollowRepository $followRepository, bool $followedAccountIsPrivate = false, ?InMemoryFollowRequestRepository $followRequestRepository = null): CreateFollow
    {
        return new CreateFollow(
            new ImmediateTransactionManager,
            new InMemoryAccountRepository($followedAccountExists, $followedAccountIsPrivate),
            $followRepository,
            new TestFollowFactory,
            $followRequestRepository ?? new InMemoryFollowRequestRepository,
            new TestFollowRequestFactory,
        );
    }

    private function input(): CreateFollowInput
    {
        return new CreateFollowInput(
            followingAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            followedAccountIdentifier: new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
        );
    }

    private function follow(): Follow
    {
        return (new TestFollowFactory)->create(
            followingAccountIdentifier: new AccountIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'),
            followedAccountIdentifier: new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'),
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

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    public function __construct(private bool $exists, private bool $private = false) {}

    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        if (! $this->exists) {
            return null;
        }

        return Account::create(
            $accountIdentifier,
            new AccountName('対象アカウント'),
            null,
            new EmailAddress('target@example.com'),
            [],
            new FavoriteTagIdentifiers([]),
            $this->private ? AccountVisibility::PRIVATE : AccountVisibility::PUBLIC,
        );
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return null;
    }

    public function save(Account $account): void {}

    public function updateProfile(Account $account): void {}
}

final class InMemoryFollowRepository implements FollowRepositoryInterface
{
    public ?Follow $existing = null;

    /** @var list<Follow> */
    public array $saved = [];

    public function find(AccountIdentifier $followingAccountIdentifier, AccountIdentifier $followedAccountIdentifier): ?Follow
    {
        if ($this->existing === null || $this->existing->followingAccountIdentifier()->value() !== $followingAccountIdentifier->value() || $this->existing->followedAccountIdentifier()->value() !== $followedAccountIdentifier->value()) {
            return null;
        }

        return $this->existing;
    }

    public function save(Follow $follow): void
    {
        $this->saved[] = $follow;
    }
}

final class TestFollowFactory implements FollowFactoryInterface
{
    public function create(AccountIdentifier $followingAccountIdentifier, AccountIdentifier $followedAccountIdentifier): Follow
    {
        return new Follow($followingAccountIdentifier, $followedAccountIdentifier);
    }
}

final class InMemoryFollowRequestRepository implements FollowRequestRepositoryInterface
{
    public ?FollowRequest $existing = null;

    /** @var list<FollowRequest> */
    public array $saved = [];

    public function find(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        return $this->matching($requestingAccountIdentifier, $targetAccountIdentifier);
    }

    public function findForUpdate(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        return $this->matching($requestingAccountIdentifier, $targetAccountIdentifier);
    }

    public function save(FollowRequest $followRequest): void
    {
        $this->saved[] = $followRequest;
    }

    private function matching(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): ?FollowRequest
    {
        if ($this->existing?->requestingAccountIdentifier()->value() !== $requestingAccountIdentifier->value() || $this->existing?->targetAccountIdentifier()->value() !== $targetAccountIdentifier->value()) {
            return null;
        }

        return $this->existing;
    }
}

final class TestFollowRequestFactory implements FollowRequestFactoryInterface
{
    public function create(AccountIdentifier $requestingAccountIdentifier, AccountIdentifier $targetAccountIdentifier): FollowRequest
    {
        return new FollowRequest($requestingAccountIdentifier, $targetAccountIdentifier);
    }
}
