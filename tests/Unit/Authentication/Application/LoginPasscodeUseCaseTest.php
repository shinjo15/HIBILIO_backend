<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication\Application;

use PHPUnit\Framework\TestCase;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Authentication\Application\Service\LoginPasscodeGeneratorServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeHashServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeMailServiceInterface;
use Src\Authentication\Application\Service\LoginPasscodeStateServiceInterface;
use Src\Authentication\Application\UseCase\GenerateLoginPasscode\GenerateLoginPasscode;
use Src\Authentication\Application\UseCase\GenerateLoginPasscode\GenerateLoginPasscodeInput;
use Src\Authentication\Application\UseCase\VerifyLoginPasscode\VerifyLoginPasscode;
use Src\Authentication\Application\UseCase\VerifyLoginPasscode\VerifyLoginPasscodeInput;
use Src\Authentication\Domain\Entity\LoginPasscodeChallenge;
use Src\Authentication\Domain\Factory\LoginPasscodeChallengeFactoryInterface;
use Src\Authentication\Domain\ValueObject\LoginPasscode;
use Src\Authentication\Domain\ValueObject\LoginPasscodeChallengeIdentifier;
use Src\Authentication\Domain\ValueObject\LoginPasscodeHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LoginPasscodeUseCaseTest extends TestCase
{
    public function test_generates_stores_and_mails_a_challenge_for_a_known_account(): void
    {
        $account = $this->account();
        $state = new InMemoryLoginPasscodeState;
        $mail = new FakeLoginPasscodeMail;
        $output = (new GenerateLoginPasscode(
            new InMemoryAccounts($account), new FixedChallengeFactory, new FixedPasscodeGenerator,
            new FakePasscodeHashService, $state, $mail,
        ))->execute(new GenerateLoginPasscodeInput(new EmailAddress('user@example.com')));

        self::assertSame('3b5581e9-16df-4879-b7d2-5d88dca6ab87', $output->challengeIdentifier());
        self::assertSame('123456', $mail->passcode?->value());
        self::assertSame('user@example.com', $state->challenge?->emailAddress()->value());
    }

    public function test_does_nothing_for_an_unknown_account(): void
    {
        $state = new InMemoryLoginPasscodeState;
        $mail = new FakeLoginPasscodeMail;
        $output = (new GenerateLoginPasscode(
            new InMemoryAccounts(null), new FixedChallengeFactory, new FixedPasscodeGenerator,
            new FakePasscodeHashService, $state, $mail,
        ))->execute(new GenerateLoginPasscodeInput(new EmailAddress('missing@example.com')));

        self::assertNull($output->challengeIdentifier());
        self::assertNull($state->challenge);
        self::assertNull($mail->passcode);
    }

    public function test_verifies_matching_passcode_and_deletes_the_challenge(): void
    {
        $state = new InMemoryLoginPasscodeState;
        $state->challenge = $this->challenge();
        $result = (new VerifyLoginPasscode($state, new FakePasscodeHashService))->execute(
            new VerifyLoginPasscodeInput($state->challenge->identifier(), new LoginPasscode('123456')),
        );

        self::assertSame('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028', $result->accountIdentifier()?->value());
        self::assertNull($state->challenge);
    }

    public function test_rejects_a_mismatched_or_missing_challenge(): void
    {
        $state = new InMemoryLoginPasscodeState;
        $state->challenge = $this->challenge();
        $useCase = new VerifyLoginPasscode($state, new FakePasscodeHashService);

        self::assertNull($useCase->execute(new VerifyLoginPasscodeInput($state->challenge->identifier(), new LoginPasscode('000000')))->accountIdentifier());
        self::assertSame(1, $state->failedAttempts);
        $state->challenge = null;
        self::assertNull($useCase->execute(new VerifyLoginPasscodeInput(new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new LoginPasscode('123456')))->accountIdentifier());
    }

    private function account(): Account
    {
        return Account::create(new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'), new AccountName('ユーザー'), null, new EmailAddress('user@example.com'), [], new FavoriteTagIdentifiers([]));
    }

    private function challenge(): LoginPasscodeChallenge
    {
        return new LoginPasscodeChallenge(new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), new AccountIdentifier('f0cfa1a3-1ac7-44af-9bf4-b36c9262f028'), new EmailAddress('user@example.com'), new LoginPasscodeHash('hashed:123456'));
    }
}
final class InMemoryAccounts implements AccountRepositoryInterface
{
    public function __construct(private ?Account $account) {}

    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        return null;
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return $this->account;
    }

    public function save(Account $account): void {}

    public function updateProfile(Account $account): void {}
}
final class FixedChallengeFactory implements LoginPasscodeChallengeFactoryInterface
{
    public function create(AccountIdentifier $accountIdentifier, EmailAddress $emailAddress, LoginPasscodeHash $passcodeHash): LoginPasscodeChallenge
    {
        return new LoginPasscodeChallenge(new LoginPasscodeChallengeIdentifier('3b5581e9-16df-4879-b7d2-5d88dca6ab87'), $accountIdentifier, $emailAddress, $passcodeHash);
    }
}
final class FixedPasscodeGenerator implements LoginPasscodeGeneratorServiceInterface
{
    public function generate(): string
    {
        return '123456';
    }
}
final class FakePasscodeHashService implements LoginPasscodeHashServiceInterface
{
    public function hash(LoginPasscode $passcode): LoginPasscodeHash
    {
        return new LoginPasscodeHash('hashed:'.$passcode->value());
    }

    public function matches(LoginPasscode $passcode, LoginPasscodeHash $hash): bool
    {
        return $hash->value() === 'hashed:'.$passcode->value();
    }
}
final class FakeLoginPasscodeMail implements LoginPasscodeMailServiceInterface
{
    public ?LoginPasscode $passcode = null;

    public function send(EmailAddress $emailAddress, LoginPasscode $passcode): void
    {
        $this->passcode = $passcode;
    }
}
final class InMemoryLoginPasscodeState implements LoginPasscodeStateServiceInterface
{
    public ?LoginPasscodeChallenge $challenge = null;

    public int $failedAttempts = 0;

    public function register(LoginPasscodeChallenge $challenge): void
    {
        $this->challenge = $challenge;
    }

    public function find(LoginPasscodeChallengeIdentifier $identifier): ?LoginPasscodeChallenge
    {
        return $this->challenge;
    }

    public function recordFailedAttempt(LoginPasscodeChallengeIdentifier $identifier): ?int
    {
        return ++$this->failedAttempts;
    }

    public function delete(LoginPasscodeChallengeIdentifier $identifier): bool
    {
        $exists = $this->challenge !== null;
        $this->challenge = null;

        return $exists;
    }
}
