<?php

declare(strict_types=1);

namespace Tests\Unit\Like\Infrastructure\Factory;

use PHPUnit\Framework\TestCase;
use Src\Like\Domain\ValueObject\LikeNotificationStatus;
use Src\Like\Domain\ValueObject\LikeNotificationTarget;
use Src\Like\Infrastructure\Factory\LikeNotificationFactory;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class LikeNotificationFactoryTest extends TestCase
{
    public function test_factory_assigns_generated_identifier_content_and_unread_status(): void
    {
        $uuid = $this->createMock(UuidServiceInterface::class);
        $uuid->expects(self::once())->method('generate')->willReturn('11111111-1111-4111-8111-111111111111');
        $recipient = new AccountIdentifier('22222222-2222-4222-8222-222222222222');
        $target = new LikeNotificationTarget($recipient, '花子', '朝の運動', true, true, true, true, true, false, false, false, false);

        $notification = (new LikeNotificationFactory($uuid))->create($target);

        self::assertSame('11111111-1111-4111-8111-111111111111', $notification->identifier()->value());
        self::assertSame($recipient, $notification->recipient());
        self::assertSame('花子さんが「朝の運動」にいいねしました。', $notification->content()->value());
        self::assertSame(LikeNotificationStatus::Unread, $notification->status());
    }
}
