<?php

declare(strict_types=1);

namespace Src\RoutineExecution\Domain\Exception;

final class AccountNotAuthenticatedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('認証されたアカウントを利用できません。');
    }
}
