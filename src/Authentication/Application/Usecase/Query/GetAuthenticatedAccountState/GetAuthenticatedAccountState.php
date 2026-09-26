<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Query\GetAuthenticatedAccountState;

use Src\Account\Domain\ValueObject\AccountStatus;
use Src\Authentication\Domain\Repository\AuthenticatedAccountStateRepositoryInterface;

final readonly class GetAuthenticatedAccountState implements GetAuthenticatedAccountStateInterface
{
    public function __construct(private AuthenticatedAccountStateRepositoryInterface $states) {}

    public function execute(GetAuthenticatedAccountStateInputPort $input): GetAuthenticatedAccountStateOutputPort
    {
        $state = $this->states->find($input->accountIdentifier());

        return new GetAuthenticatedAccountStateOutput($state !== null && $state->available() && $state->status() === AccountStatus::ACTIVE);
    }
}
