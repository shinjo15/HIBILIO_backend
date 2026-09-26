<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginExpiresAt;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;
use Src\Authentication\Domain\ValueObject\PersistentLoginValidatorHash;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class PersistentLoginTokenRepository implements PersistentLoginTokenRepositoryInterface
{
    public function findBySelector(PersistentLoginSelector $selector): ?PersistentLoginToken
    {
        $record = DB::table('persistent_login_tokens')->where('selector', $selector->value())->first();

        if ($record === null) {
            return null;
        }

        return new PersistentLoginToken(
            new PersistentLoginSelector($record->selector),
            new AccountIdentifier($record->account_identifier),
            new PersistentLoginValidatorHash($record->validator_hash),
            new PersistentLoginExpiresAt(new DateTimeImmutable($record->expires_at)),
        );
    }

    public function save(PersistentLoginToken $token): void
    {
        DB::table('persistent_login_tokens')->insert([
            'selector' => $token->selector()->value(),
            'account_identifier' => $token->accountIdentifier()->value(),
            'validator_hash' => $token->validatorHash()->value(),
            'expires_at' => $token->expiresAt()->value(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function deleteBySelector(PersistentLoginSelector $selector): void
    {
        DB::table('persistent_login_tokens')->where('selector', $selector->value())->delete();
    }
}
