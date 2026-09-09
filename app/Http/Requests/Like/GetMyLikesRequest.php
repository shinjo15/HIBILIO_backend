<?php

declare(strict_types=1);

namespace App\Http\Requests\Like;

use Illuminate\Foundation\Http\FormRequest;
use Src\Like\Application\Usecase\Query\GetMyLikes\GetMyLikesInput;

final class GetMyLikesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'number_of_items_per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function toInput(string $accountIdentifier): GetMyLikesInput
    {
        return new GetMyLikesInput(
            accountIdentifier: $accountIdentifier,
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
