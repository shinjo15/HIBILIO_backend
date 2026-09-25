<?php

declare(strict_types=1);

namespace Src\Authentication\Infrastructure\Repository;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Authentication\Domain\Entity\PersistentLoginToken;
use Src\Authentication\Domain\Repository\PersistentLoginTokenRepositoryInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class PersistentLoginTokenRepository implements PersistentLoginTokenRepositoryInterface
{
    public function find(string $selector): ?PersistentLoginToken
    {
        $token = DB::table('persistent_login_tokens')->where('selector', $selector)->first();

        if ($token === null) {
            return null;
        }

        return new PersistentLoginToken(
            $token->selector,
            new AccountIdentifier($token->account_identifier),
            $token->validator_hash,
            new DateTimeImmutable($token->expires_at),
        );
    }

    public function save(PersistentLoginToken $token): void
    {
        DB::table('persistent_login_tokens')->insert([
            'selector' => $token->selector(),
            'account_identifier' => $token->accountIdentifier()->value(),
            'validator_hash' => $token->validatorHash(),
            'expires_at' => $token->expiresAt(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function delete(string $selector): void
    {
        DB::table('persistent_login_tokens')->where('selector', $selector)->delete();
    }
}
