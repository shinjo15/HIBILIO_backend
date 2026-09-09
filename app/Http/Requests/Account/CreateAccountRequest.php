<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Account\Application\Usecase\Command\CreateAccount\CreateAccountInput;
use Src\Account\Domain\ValueObject\AccountBio;
use Src\Account\Domain\ValueObject\AccountHeader;
use Src\Account\Domain\ValueObject\AccountIcon;
use Src\Account\Domain\ValueObject\AccountName;
use Src\Account\Domain\ValueObject\EmailAddress;
use Src\Account\Domain\ValueObject\FavoriteTagIdentifiers;
use Src\Account\Domain\ValueObject\SocialLink;
use Src\Account\Domain\ValueObject\SocialType;
use Src\Account\Domain\ValueObject\SocialUrl;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class CreateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_name' => ['required', 'string', 'max:50'],
            'account_bio' => ['nullable', 'string', 'max:300'],
            'social_links' => ['present', 'array'],
            'social_links.*.social_type' => ['required', Rule::enum(SocialType::class)],
            'social_links.*.social_url' => ['required', 'url'],
            'favorite_tag_identifiers' => ['present', 'array'],
            'favorite_tag_identifiers.*' => ['required', 'uuid'],
            'icon_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'header_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
        ];
    }

    public function toInput(EmailAddress $emailAddress, ?AccountIcon $icon, ?AccountHeader $header): CreateAccountInput
    {
        $validated = $this->validated();

        return new CreateAccountInput(
            new AccountName($validated['account_name']),
            isset($validated['account_bio']) ? new AccountBio($validated['account_bio']) : null,
            $emailAddress,
            array_map(
                static fn (array $link): SocialLink => new SocialLink(
                    SocialType::from($link['social_type']),
                    new SocialUrl($link['social_url']),
                ),
                $validated['social_links'],
            ),
            new FavoriteTagIdentifiers(
                array_map(
                    static fn (string $identifier): TagIdentifier => new TagIdentifier($identifier), $validated['favorite_tag_identifiers']),
            ),
            $icon,
            $header,
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
