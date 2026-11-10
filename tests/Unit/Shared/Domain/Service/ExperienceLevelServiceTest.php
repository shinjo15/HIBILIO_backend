<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Service;

use PHPUnit\Framework\TestCase;
use Src\Shared\Domain\Service\ExperienceLevelService;

final class ExperienceLevelServiceTest extends TestCase
{
    public function test_level_boundaries(): void
    {
        $service = new ExperienceLevelService;
        self::assertSame(1, $service->levelForExperience(0));
        self::assertSame(1, $service->levelForExperience(2999));
        self::assertSame(2, $service->levelForExperience(3000));
        self::assertSame(2, $service->levelForExperience(5999));
        self::assertSame(3, $service->levelForExperience(6000));
    }
}
