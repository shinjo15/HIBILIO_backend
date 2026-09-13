<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\ValueObject;

use InvalidArgumentException;
use Src\Account\Domain\ValueObject\EmailAddress;

final readonly class PendingSocialRegistration
{
    private EmailAddress $emailAddressValue;

    public function __construct(
        private SocialLoginProvider $provider,
        private string $providerUserIdentifier,
        string|EmailAddress $emailAddress,
    ) {
        if (trim($providerUserIdentifier) === '' || strlen($providerUserIdentifier) > 255) {
            throw new InvalidArgumentException('ソーシャルログインのユーザー識別子が不正です。');
        }

        $this->emailAddressValue = $emailAddress instanceof EmailAddress
            ? $emailAddress
            : new EmailAddress($emailAddress);
    }

    public function provider(): SocialLoginProvider
    {
        return $this->provider;
    }

    public function providerUserIdentifier(): string
    {
        return $this->providerUserIdentifier;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddressValue;
    }
}
