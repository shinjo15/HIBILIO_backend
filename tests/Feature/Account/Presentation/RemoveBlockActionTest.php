<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Presentation;

use Src\Account\Application\Usecase\Command\RemoveBlock\RemoveBlockInputPort;
use Src\Account\Application\Usecase\Command\RemoveBlock\RemoveBlockInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Tests\TestCase;

final class RemoveBlockActionTest extends TestCase
{
    public function test_removes_a_block_for_the_authenticated_account(): void
    {
        $removeBlock = new class implements RemoveBlockInterface
        {
            public ?RemoveBlockInputPort $input = null;

            public function execute(RemoveBlockInputPort $input): void
            {
                $this->input = $input;
            }
        };
        $this->app->instance(RemoveBlockInterface::class, $removeBlock);
        $this->authenticateAsAccount();

        $this->deleteJson('/api/blocks/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->assertNoContent();

        self::assertSame('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', $removeBlock->input?->blockingAccountIdentifier()->value());
        self::assertSame('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', $removeBlock->input?->blockedAccountIdentifier()->value());
    }

    public function test_returns_unauthorized_without_an_authenticated_account(): void
    {
        $this->deleteJson('/api/blocks/bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb')->assertUnauthorized();
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
