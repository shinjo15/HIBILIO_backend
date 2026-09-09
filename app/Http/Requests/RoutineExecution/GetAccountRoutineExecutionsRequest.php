<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInput;

final class GetAccountRoutineExecutionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['page' => ['nullable', 'integer', 'min:1'], 'number_of_items_per_page' => ['nullable', 'integer', 'min:1']];
    }

    public function toInput(): GetAccountRoutineExecutionsInput
    {
        return new GetAccountRoutineExecutionsInput((string) $this->route('account_identifier'), $this->integer('page', 1), $this->integer('number_of_items_per_page', 20));
    }
}
