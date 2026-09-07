<?php

declare(strict_types=1);

namespace Src\Tag\Application\Usecase\Query\GetPickupTags;

interface GetPickupTagsInterface
{
    public function execute(): GetPickupTagsOutputPort;
}
