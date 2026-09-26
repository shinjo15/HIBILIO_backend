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
    public function find(PersistentLoginSelector $selector): ?PersistentLoginToken
    {
        $token = DB::table('persistent_login_tokens')->where('selector', $selector->value())->first();

        if ($token === null) {
            return null;
        }

        return new PersistentLoginToken(
            new PersistentLoginSelector($token->selector),
            new AccountIdentifier($token->account_identifier),
            new PersistentLoginValidatorHash($token->validator_hash),
            new PersistentLoginExpiresAt(new DateTimeImmutable($token->expires_at)),
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

    public function delete(PersistentLoginSelector $selector): void
    {
        DB::table('persistent_login_tokens')->where('selector', $selector->value())->delete();
    }
}
