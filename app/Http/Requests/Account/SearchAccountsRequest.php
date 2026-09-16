<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\SearchAccounts\SearchAccountsInput;

final class SearchAccountsRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'number_of_items_per_page' => ['nullable', 'integer', 'min:1'],
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
            page: $this->positiveInteger('page', 1),
            numberOfItemsPerPage: $this->positiveInteger('number_of_items_per_page', 20),
        );
    }

    private function positiveInteger(string $key, int $default): int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : $default;
    }
}
