<?php

declare(strict_types=1);

namespace Src\ListeningContent\Infrastructure\Factory;

use Src\ListeningContent\Domain\Entity\ListeningContent;
use Src\ListeningContent\Domain\Factory\ListeningContentFactoryInterface;
use Src\ListeningContent\Domain\ValueObject\ListeningContentIdentifier;
use Src\ListeningContent\Domain\ValueObject\ListeningContentUrl;
use Src\Shared\Application\Service\UuidServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ListeningContentFactory implements ListeningContentFactoryInterface
{
    public function __construct(private UuidServiceInterface $uuidService) {}

    public function create(AccountIdentifier $accountIdentifier, ListeningContentUrl $url): ListeningContent
    {
        return new ListeningContent(new ListeningContentIdentifier($this->uuidService->generate()), $accountIdentifier, $url);
    }
}
