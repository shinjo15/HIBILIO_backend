<?php

declare(strict_types=1);

namespace Src\Authentication\Domain\Exception;

use DomainException;

final class RegistrationEmailAddressAlreadyRegisteredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('このメールアドレスはすでに登録されています。');
    }
}
