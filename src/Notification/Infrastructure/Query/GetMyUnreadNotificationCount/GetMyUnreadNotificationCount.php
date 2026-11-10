<?php

declare(strict_types=1);

namespace Src\Notification\Infrastructure\Query\GetMyUnreadNotificationCount;

use Illuminate\Support\Facades\DB;
use Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount\GetMyUnreadNotificationCountInputPort;
use Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount\GetMyUnreadNotificationCountInterface;
use Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount\GetMyUnreadNotificationCountOutput;
use Src\Notification\Application\Usecase\Query\GetMyUnreadNotificationCount\GetMyUnreadNotificationCountOutputPort;
use Src\Notification\Domain\ValueObject\PersonalNotificationStatus;

final class GetMyUnreadNotificationCount implements GetMyUnreadNotificationCountInterface
{
    public function execute(GetMyUnreadNotificationCountInputPort $input): GetMyUnreadNotificationCountOutputPort
    {
        $count = DB::table('personal_notifications')
            ->where('receiver_account_identifier', $input->accountIdentifier())
            ->where('status', PersonalNotificationStatus::Unread->value)
            ->count();

        return new GetMyUnreadNotificationCountOutput($count);
    }
}
