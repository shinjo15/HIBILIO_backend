<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\GetAccountRoutinePosts\GetAccountRoutinePostsInput;

final class GetMyRoutinePostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1'], 'number_of_items_per_page' => ['nullable', 'integer', 'min:1']];
    }

    public function toInput(string $accountIdentifier): GetAccountRoutinePostsInput
    {
        return new GetAccountRoutinePostsInput($accountIdentifier, $this->positiveInteger('page', 1), $this->positiveInteger('number_of_items_per_page', 20));
    }

    private function positiveInteger(string $key, int $default): int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : $default;
    }
}
