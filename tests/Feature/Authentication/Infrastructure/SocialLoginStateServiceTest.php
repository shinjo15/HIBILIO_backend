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
    public function test_state_is_short_lived_one_time_and_bound_to_the_browser_session(): void
    {
        $service = new CacheSocialLoginStateService;
        $service->store(new SocialLoginTemporaryInfoSupport(
            'state-token',
            SocialLoginProvider::GOOGLE,
            hash('sha256', 'browser'),
            'verifier',
            'nonce',
        ));

        self::assertNull($service->consume('state-token', SocialLoginProvider::GOOGLE, 'other-browser'));
        self::assertNull(Cache::get('social-login:state:state-token'));
        self::assertNull($service->consume('state-token', SocialLoginProvider::GOOGLE, 'browser'));
    }

    public function test_matching_state_can_only_be_consumed_once(): void
    {
        $service = new CacheSocialLoginStateService;
        $service->store(new SocialLoginTemporaryInfoSupport(
            'state-token',
            SocialLoginProvider::APPLE,
            hash('sha256', 'browser'),
            'verifier',
            'nonce',
        ));

        self::assertNotNull($service->consume('state-token', SocialLoginProvider::APPLE, 'browser'));
        self::assertNull($service->consume('state-token', SocialLoginProvider::APPLE, 'browser'));
    }
}
