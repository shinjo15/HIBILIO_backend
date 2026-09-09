<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Src\Account\Application\UseCase\UpdateAccountProfile\UpdateAccountProfileInput;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Account\Domain\ValueObject\SocialType;
use Src\Account\Domain\ValueObject\SocialUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class UpdateAccountProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_name' => ['sometimes', 'required', 'string', 'max:50'],
            'account_bio' => ['sometimes', 'nullable', 'string', 'max:300'],
            'social_links' => ['sometimes', 'array'],
            'social_links.*.social_type' => ['required', Rule::enum(SocialType::class)],
            'social_links.*.social_url' => ['required', 'url'],
            'favorite_tag_identifiers' => ['sometimes', 'array'],
            'favorite_tag_identifiers.*' => ['required', 'uuid'],
            'icon_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'icon_image_deleted' => ['sometimes', 'boolean'],
            'header_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'header_image_deleted' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->hasFile('icon_image') && $this->boolean('icon_image_deleted')) {
                $validator->errors()->add('icon_image', 'アイコン画像と削除指定は同時に指定できません。');
            }
            if ($this->hasFile('header_image') && $this->boolean('header_image_deleted')) {
                $validator->errors()->add('header_image', 'ヘッダー画像と削除指定は同時に指定できません。');
            }
        }];
    }

    public function toInput(string $accountIdentifier, ?AccountIcon $icon, ?AccountHeader $header): UpdateAccountProfileInput
    {
        $validated = $this->validated();

        return new UpdateAccountProfileInput(
            new AccountIdentifier($accountIdentifier),
            array_key_exists('account_name', $validated) ? new AccountName($validated['account_name']) : null,
            array_key_exists('account_bio', $validated),
            ! array_key_exists('account_bio', $validated) || $validated['account_bio'] === null ? null : new AccountBio($validated['account_bio']),
            array_key_exists('social_links', $validated) ? array_map(static fn (array $link): SocialLink => new SocialLink(SocialType::from($link['social_type']), new SocialUrl($link['social_url'])), $validated['social_links']) : null,
            array_key_exists('favorite_tag_identifiers', $validated) ? new FavoriteTagIdentifiers(array_map(static fn (string $identifier): TagIdentifier => new TagIdentifier($identifier), $validated['favorite_tag_identifiers'])) : null,
            $icon,
            $this->boolean('icon_image_deleted'),
            $header,
            $this->boolean('header_image_deleted'),
        );
    }

    public function iconImageContents(): ?string
    {
        return $this->file('icon_image')?->getContent();
    }

    public function headerImageContents(): ?string
    {
        return $this->file('header_image')?->getContent();
    }
}
