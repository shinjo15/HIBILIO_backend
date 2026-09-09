<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInput;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'routine_identifier' => ['required', 'uuid'],
            'executed_routine_action_identifiers' => ['present', 'array', 'min:1', 'distinct'],
            'executed_routine_action_identifiers.*' => ['required', 'uuid'],
            'routine_execution_memo' => ['nullable', 'string', 'max:31'],
        ];
    }

    public function toInput(string $accountIdentifier): CreateRoutineExecutionInput
    {
        $validated = $this->validated();

        return new CreateRoutineExecutionInput(
            new AccountIdentifier($accountIdentifier),
            new RoutineIdentifier($validated['routine_identifier']),
            array_map(static fn (string $identifier): RoutineActionIdentifier => new RoutineActionIdentifier($identifier), $validated['executed_routine_action_identifiers']),
            isset($validated['routine_execution_memo']) ? new RoutineExecutionMemo($validated['routine_execution_memo']) : null,
        );
    }
}
