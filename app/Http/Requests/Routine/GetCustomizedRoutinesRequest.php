<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use Illuminate\Foundation\Http\FormRequest;
use Src\Routine\Application\Usecase\Query\GetCustomizedRoutines\GetCustomizedRoutinesInput;

final class GetCustomizedRoutinesRequest extends FormRequest
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

    public function toInput(): GetCustomizedRoutinesInput
    {
        return new GetCustomizedRoutinesInput(
            parentRoutineIdentifier: (string) $this->route('routine_identifier'),
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
