<?php

declare(strict_types=1);

namespace Src\ListeningContent\Application\UseCase\CreateListeningContent;

use Src\ListeningContent\Domain\Factory\ListeningContentFactoryInterface;
use Src\ListeningContent\Domain\Repository\ListeningContentRepositoryInterface;

final readonly class CreateListeningContent implements CreateListeningContentInterface
{
    public function __construct(
        private ListeningContentFactoryInterface $factory,
        private ListeningContentRepositoryInterface $repository,
    ) {}

    public function execute(CreateListeningContentInputPort $input): void
    {
        $this->repository->save($this->factory->create($input->accountIdentifier(), $input->url()));
    }
}
