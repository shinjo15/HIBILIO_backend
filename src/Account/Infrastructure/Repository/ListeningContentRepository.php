<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Repository;

use App\Models\ListeningContentModel;
use Src\Account\Domain\Entity\ListeningContent;
use Src\Account\Domain\Repository\ListeningContentRepositoryInterface;

final class ListeningContentRepository implements ListeningContentRepositoryInterface
{
    public function save(ListeningContent $content): void
    {
        ListeningContentModel::query()->create([
            'listening_content_identifier' => $content->identifier()->value(),
            'account_identifier' => $content->accountIdentifier()->value(),
            'url' => $content->url()->value(),
        ]);
    }
}
