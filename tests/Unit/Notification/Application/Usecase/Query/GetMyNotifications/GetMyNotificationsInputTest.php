<?php

declare(strict_types=1);

namespace Tests\Unit\Notification\Application\Usecase\Query\GetMyNotifications;

use PHPUnit\Framework\TestCase;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInput;
use Src\Notification\Application\Usecase\Query\GetMyNotifications\GetMyNotificationsInputPort;

final class GetMyNotificationsInputTest extends TestCase
{
    public function test_exposes_account_identifier_as_a_string(): void
    {
        $identifier = '11111111-1111-4111-8111-111111111111';
        $input = new GetMyNotificationsInput($identifier, 2, 10);

        self::assertInstanceOf(GetMyNotificationsInputPort::class, $input);
        self::assertSame($identifier, $input->accountIdentifier());
        self::assertSame(2, $input->page());
        self::assertSame(10, $input->numberOfItemsPerPage());
    }
}
