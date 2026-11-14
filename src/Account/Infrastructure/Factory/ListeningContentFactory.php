<?php

declare(strict_types=1);

namespace Src\Account\Infrastructure\Factory;

use Src\Account\Domain\Entity\ListeningContent;
use Src\Account\Domain\Factory\ListeningContentFactoryInterface;
use Src\Account\Domain\ValueObject\ListeningContentIdentifier;
use Src\Account\Domain\ValueObject\ListeningContentUrl;
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
