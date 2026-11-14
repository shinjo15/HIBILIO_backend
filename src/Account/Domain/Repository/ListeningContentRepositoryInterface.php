<?php

declare(strict_types=1);

namespace Src\Account\Domain\Repository;

use Src\Account\Domain\Entity\ListeningContent;

interface ListeningContentRepositoryInterface
{
    public function save(ListeningContent $content): void;
}
