<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use App\Http\Requests\PaginatedRequest;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesInput;

final class SearchRoutinesRequest extends PaginatedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'min:1', 'max:50', 'required_without:tag_identifiers'],
            'tag_identifiers' => ['nullable', 'array', 'min:1', 'required_without:title'],
            'tag_identifiers.*' => ['required', 'uuid', 'distinct'],
            ...$this->paginationRules(),
        ];
    }

    public function toInput(?string $accountIdentifier): SearchRoutinesInput
    {
        $title = $this->validated('title');
        $tagIdentifiers = $this->validated('tag_identifiers', []);

        return new SearchRoutinesInput(
            accountIdentifier: $accountIdentifier,
            title: is_string($title) ? $title : null,
            tagIdentifiers: is_array($tagIdentifiers) ? array_values(array_map(static fn (mixed $identifier): string => (string) $identifier, $tagIdentifiers)) : [],
            page: $this->page(),
            numberOfItemsPerPage: $this->numberOfItemsPerPage(),
        );
    }
}
