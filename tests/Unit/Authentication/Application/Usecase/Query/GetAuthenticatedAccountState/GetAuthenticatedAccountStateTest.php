<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountState;
use Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState\GetAuthenticatedAccountStateInput;
use Src\Authentication\Domain\Repository\AuthenticatedAccountStateRepositoryInterface;
use Src\Authentication\Domain\ValueObject\AuthenticatedAccountState;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class GetAuthenticatedAccountStateTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    #[DataProvider('accountStates')]
    public function test_authentication_requires_an_available_active_account(bool $available, AccountStatus $status, bool $expected): void
    {
        $identifier = new AccountIdentifier('11111111-1111-4111-8111-111111111111');
        $repository = Mockery::mock(AuthenticatedAccountStateRepositoryInterface::class);
        $repository->shouldReceive('find')->once()->with($identifier)->andReturn(new AuthenticatedAccountState($available, $status));

        $output = (new GetAuthenticatedAccountState($repository))->execute(new GetAuthenticatedAccountStateInput($identifier));

        self::assertSame($expected, $output->isAuthenticated());
    }

    /** @return array<string, array{bool, AccountStatus, bool}> */
    public static function accountStates(): array
    {
        return [
            'active' => [true, AccountStatus::ACTIVE, true],
            'unavailable' => [false, AccountStatus::ACTIVE, false],
            'temporarily banned' => [true, AccountStatus::TEMPORARILY_BANNED, false],
            'permanently banned' => [true, AccountStatus::PERMANENTLY_BANNED, false],
        ];
    }
}
