<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use Illuminate\Foundation\Http\FormRequest;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesInput;

final class SearchRoutinesRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'number_of_items_per_page' => ['nullable', 'integer', 'min:1'],
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
