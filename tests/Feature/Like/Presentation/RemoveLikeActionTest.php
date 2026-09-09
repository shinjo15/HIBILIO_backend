<?php

declare(strict_types=1);

namespace Tests\Feature\Like\Presentation;

use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInputPort;
use Src\Like\Application\UseCase\RemoveLike\RemoveLikeInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class RemoveLikeActionTest extends TestCase
{
    public function test_removes_a_like_for_the_authenticated_account(): void
    {
        $removeLike = new class implements RemoveLikeInterface
        {
            public ?RemoveLikeInputPort $input = null;

            public function execute(RemoveLikeInputPort $input): void
            {
                $this->input = $input;
            }
        };
        $this->app->instance(RemoveLikeInterface::class, $removeLike);
        $this->authenticateAsAccount();

        $this->deleteJson('/api/likes/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->assertNoContent();

        self::assertSame('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $removeLike->input?->accountIdentifier()->value());
        self::assertSame('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $removeLike->input?->postIdentifier()->value());
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->deleteJson('/api/likes/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->assertUnauthorized();
    }

    private function authenticateAsAccount(): void
    {
        $this->app->instance(AuthServiceInterface::class, new class implements AuthServiceInterface
        {
            public function login(AccountIdentifier $accountIdentifier): void {}

            public function accountIdentifier(): string
            {
                return 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
            }
        });
    }
}
