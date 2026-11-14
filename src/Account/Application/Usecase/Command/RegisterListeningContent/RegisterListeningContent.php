<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Command\RegisterListeningContent;

use Src\Account\Domain\Factory\ListeningContentFactoryInterface;
use Src\Account\Domain\Repository\ListeningContentRepositoryInterface;

final readonly class RegisterListeningContent implements RegisterListeningContentInterface
{
    public function __construct(
        private ListeningContentFactoryInterface $factory,
        private ListeningContentRepositoryInterface $repository,
    ) {}

    public function execute(RegisterListeningContentInputPort $input): void
    {
        $this->repository->save($this->factory->create($input->accountIdentifier(), $input->url()));
    }
}
