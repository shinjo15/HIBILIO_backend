<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication\Infrastructure;

use Illuminate\Support\Facades\Cache;
use Src\Authentication\Domain\ValueObject\SocialLoginProvider;
use Src\Authentication\Infrastructure\Service\CacheSocialLoginStateService;
use Src\Authentication\Infrastructure\Service\SocialLogin\SocialLoginTemporaryInfoSupport;
use Tests\TestCase;

final class SocialLoginStateServiceTest extends TestCase
{
    public function test_state_contains_account_name_and_can_only_be_consumed_once_for_the_same_browser(): void
    {
        $service = new CacheSocialLoginStateService;
        $temporaryInfo = new SocialLoginTemporaryInfoSupport(
            'state-token', SocialLoginProvider::GOOGLE, hash('sha256', 'browser'), 'verifier', 'nonce', 'New User',
        );
        $service->store($temporaryInfo);

        $consumed = $service->consume('state-token', SocialLoginProvider::GOOGLE, 'browser');

        self::assertSame('New User', $consumed?->accountName());
        self::assertNull($service->consume('state-token', SocialLoginProvider::GOOGLE, 'browser'));
    }

    public function test_state_is_rejected_and_consumed_when_provider_or_browser_does_not_match(): void
    {
        $service = new CacheSocialLoginStateService;
        $service->store(new SocialLoginTemporaryInfoSupport(
            'state-token', SocialLoginProvider::APPLE, hash('sha256', 'browser'), 'verifier', 'nonce', null,
        ));

        self::assertNull($service->consume('state-token', SocialLoginProvider::GOOGLE, 'browser'));
        self::assertNull(Cache::get('social-login:state:state-token'));
    }
}
