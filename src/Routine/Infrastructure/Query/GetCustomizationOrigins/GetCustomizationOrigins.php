<?php

declare(strict_types=1);

namespace Src\Routine\Infrastructure\Query\GetCustomizationOrigins;

use Illuminate\Support\Facades\DB;
use Src\Account\Application\Service\AccountImageUrlServiceInterface;
use Src\Routine\Application\Usecase\Query\GetCustomizationOrigins\GetCustomizationOriginsInputPort;
use Src\Routine\Application\Usecase\Query\GetCustomizationOrigins\GetCustomizationOriginsInterface;
use Src\Routine\Application\Usecase\Query\GetCustomizationOrigins\GetCustomizationOriginsOutput;
use Src\Routine\Application\Usecase\Query\GetCustomizationOrigins\GetCustomizationOriginsOutputPort;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Infrastructure\Query\Account\PrivateAccountVisibility;
use Src\Shared\Infrastructure\Query\Block\BlockVisibility;

final readonly class GetCustomizationOrigins implements GetCustomizationOriginsInterface
{
    public function __construct(private AccountImageUrlServiceInterface $accountImageUrlService) {}

    public function execute(GetCustomizationOriginsInputPort $input): GetCustomizationOriginsOutputPort
    {
        if ($input->routineIdentifiers() === []) {
            return new GetCustomizationOriginsOutput([]);
        }

        $query = DB::table('routines as children')
            ->join('routines as parents', 'children.parent_routine_identifier', '=', 'parents.routine_identifier')
            ->join('accounts as authors', 'parents.account_identifier', '=', 'authors.account_identifier')
            ->whereIn('children.routine_identifier', array_unique($input->routineIdentifiers()))
            ->where('parents.available', true)
            ->where('authors.available', true)
            ->where('authors.status', 'active');

        if ($input->viewerAccountIdentifier() !== null) {
            BlockVisibility::exclude($query, $input->viewerAccountIdentifier(), 'authors.account_identifier');
        }
        PrivateAccountVisibility::exclude($query, $input->viewerAccountIdentifier(), 'authors.account_identifier', 'authors.visibility');

        $rows = $query->get(['children.routine_identifier as child_identifier', 'parents.routine_identifier', 'parents.routine_name', 'authors.account_identifier', 'authors.account_name']);
        $icons = [];
        $origins = [];
        foreach ($rows as $row) {
            $author = (string) $row->account_identifier;
            if (! array_key_exists($author, $icons)) {
                $icons[$author] = $this->accountImageUrlService->iconImageUrl(new AccountIdentifier($author));
            }
            $origins[(string) $row->child_identifier] = [
                'routineIdentifier' => (string) $row->routine_identifier,
                'routineName' => (string) $row->routine_name,
                'accountName' => (string) $row->account_name,
                'iconImageUrl' => $icons[$author],
            ];
        }

        return new GetCustomizationOriginsOutput($origins);
    }
}
