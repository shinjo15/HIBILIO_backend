<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\Usecase\Query\GetRoutineExecutionDetails\GetRoutineExecutionDetailsInput;

final class GetRoutineExecutionDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [];
    }

    public function toInput(?string $accountIdentifier): GetRoutineExecutionDetailsInput
    {
        return new GetRoutineExecutionDetailsInput(
            routineExecutionIdentifier: (string) $this->route('routine_execution_identifier'),
            accountIdentifier: $accountIdentifier,
        );
    }
}
