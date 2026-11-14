<?php

declare(strict_types=1);

namespace Src\ListeningContent\Domain\Repository;

use Src\ListeningContent\Domain\Entity\ListeningContent;

interface ListeningContentRepositoryInterface
{
    public function save(ListeningContent $content): void;
}
