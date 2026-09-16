<?php

declare(strict_types=1);

namespace Src\Account\Application\Usecase\Query\SearchAccounts;

interface SearchAccountsInterface
{
    public function execute(SearchAccountsInputPort $input): SearchAccountsOutputPort;
}
