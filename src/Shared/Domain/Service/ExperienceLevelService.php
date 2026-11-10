<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Service;

final class ExperienceLevelService
{
    public function levelForExperience(int $experience): int
    {
        return intdiv($experience, 3000) + 1;
    }
}
