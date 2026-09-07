<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\GetPopularRoutinePosts\GetPopularRoutinePostsInput;

final class GetPopularRoutinePostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'page' => ['required', 'integer', 'min:1'],
            'number_of_items_per_page' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toInput(?string $accountIdentifier): GetPopularRoutinePostsInput
    {
        return new GetPopularRoutinePostsInput(
            accountIdentifier: $accountIdentifier,
            page: (int) $this->validated('page'),
            numberOfItemsPerPage: (int) $this->validated('number_of_items_per_page'),
        );
    }
}
