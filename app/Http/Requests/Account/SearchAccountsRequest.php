<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Http\Requests\PaginatedRequest;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsInput;

final class SearchAccountsRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'account_name' => ['nullable', 'string', 'min:1', 'max:50', 'required_without:tag_identifiers'],
            'tag_identifiers' => ['nullable', 'array', 'min:1', 'required_without:account_name'],
            'tag_identifiers.*' => ['required', 'uuid', 'distinct'],
            ...$this->paginationRules(),
        ];
    }

    public function toInput(?string $accountIdentifier): SearchAccountsInput
    {
        $accountName = $this->validated('account_name');
        $tagIdentifiers = $this->validated('tag_identifiers', []);

        return new SearchAccountsInput(
            accountIdentifier: $accountIdentifier,
            accountName: is_string($accountName) ? $accountName : null,
            tagIdentifiers: is_array($tagIdentifiers) ? array_values(array_map(static fn (mixed $identifier): string => (string) $identifier, $tagIdentifiers)) : [],
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
        );
    }
}
