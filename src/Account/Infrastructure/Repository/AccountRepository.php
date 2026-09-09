<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Repository;

use App\Models\AccountModel;
use App\Models\AccountSocialLinkModel;
use App\Models\FavoriteTagModel;
use Illuminate\Support\Facades\DB;
use Src\Account\Domain\Entity\Account;
use Src\Account\Domain\Repository\AccountRepositoryInterface;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Account\Domain\ValueObject\AccountVisibility;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Account\Domain\ValueObject\SocialType;
use Src\Account\Domain\ValueObject\SocialUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class AccountRepository implements AccountRepositoryInterface
{
    public function find(AccountIdentifier $accountIdentifier): ?Account
    {
        return $this->restore(AccountModel::query()->with(['socialLinks', 'favoriteTags'])->where('account_identifier', $accountIdentifier->value())->first());
    }

    public function findByEmailAddress(EmailAddress $emailAddress): ?Account
    {
        return $this->restore(AccountModel::query()->with(['socialLinks', 'favoriteTags'])->where('email_address', $emailAddress->value())->first());
    }

    public function save(Account $account): void
    {
        DB::transaction(function () use ($account): void {
            $values = ['account_name' => $account->accountName()->value(), 'account_bio' => $account->accountBio()?->value(), 'email_address' => $account->emailAddress()->value(), 'status' => $account->status()->value, 'ban_until' => $account->banUntil(), 'visibility' => $account->visibility()->value];
            $model = AccountModel::query()->find($account->accountIdentifier()->value());
            if ($model !== null) {
                $model->update($values);

                return;
            }
            $model = AccountModel::query()->create(['account_identifier' => $account->accountIdentifier()->value(), 'available' => true, ...$values]);
            $model->socialLinks()->createMany(array_map(static fn (SocialLink $link, int $position): array => ['type' => $link->socialType()->value, 'url' => $link->socialUrl()->value(), 'position' => $position], $account->socialLinks(), array_keys($account->socialLinks())));
            $model->favoriteTags()->createMany(array_map(static fn (TagIdentifier $id): array => ['tag_identifier' => $id->value()], $account->favoriteTagIdentifiers()->values()));
        });
    }

    public function updateProfile(Account $account): void
    {
        DB::transaction(function () use ($account): void {
            $accountIdentifier = $account->accountIdentifier()->value();
            AccountModel::query()->where('account_identifier', $accountIdentifier)->update([
                'account_name' => $account->accountName()->value(),
                'account_bio' => $account->accountBio()?->value(),
            ]);

            $socialLinkRows = array_map(static fn (SocialLink $link, int $position): array => [
                'account_identifier' => $accountIdentifier,
                'type' => $link->socialType()->value,
                'url' => $link->socialUrl()->value(),
                'position' => $position,
            ], $account->socialLinks(), array_keys($account->socialLinks()));
            $socialLinkPositions = array_column($socialLinkRows, 'position');
            $socialLinks = AccountSocialLinkModel::query()->where('account_identifier', $accountIdentifier);
            if ($socialLinkPositions === []) {
                $socialLinks->delete();
            } else {
                $socialLinks->whereNotIn('position', $socialLinkPositions)->delete();
                AccountSocialLinkModel::query()->upsert($socialLinkRows, ['account_identifier', 'position'], ['type', 'url']);
            }

            $favoriteTagRows = array_map(static fn (TagIdentifier $tagIdentifier): array => [
                'account_identifier' => $accountIdentifier,
                'tag_identifier' => $tagIdentifier->value(),
            ], $account->favoriteTagIdentifiers()->values());
            $favoriteTagIdentifiers = array_column($favoriteTagRows, 'tag_identifier');
            $favoriteTags = FavoriteTagModel::query()->where('account_identifier', $accountIdentifier);
            if ($favoriteTagIdentifiers === []) {
                $favoriteTags->delete();
            } else {
                $favoriteTags->whereNotIn('tag_identifier', $favoriteTagIdentifiers)->delete();
                FavoriteTagModel::query()->upsert($favoriteTagRows, ['account_identifier', 'tag_identifier'], ['updated_at']);
            }
        });
    }

    private function restore(?AccountModel $model): ?Account
    {
        if ($model === null) {
            return null;
        }

        return Account::restore(new AccountIdentifier($model->account_identifier), new AccountName($model->account_name), $model->account_bio === null ? null : new AccountBio($model->account_bio), new EmailAddress($model->email_address), $model->socialLinks->map(static fn (AccountSocialLinkModel $link): SocialLink => new SocialLink(SocialType::from($link->type), new SocialUrl($link->url)))->all(), new FavoriteTagIdentifiers($model->favoriteTags->map(static fn (FavoriteTagModel $tag): TagIdentifier => new TagIdentifier($tag->tag_identifier))->all()), AccountStatus::from($model->status), $model->ban_until, AccountVisibility::from($model->visibility));
    }
}
