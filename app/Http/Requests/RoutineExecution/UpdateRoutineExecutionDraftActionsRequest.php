<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions\UpdateRoutineExecutionDraftActionsInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class UpdateRoutineExecutionDraftActionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'routine_identifier' => ['required', 'uuid'],
            'routine_action_identifier' => ['required', 'uuid'],
            'is_executed' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['routine_identifier' => $this->route('routine_identifier')]);
    }

    public function toInput(string $accountIdentifier): UpdateRoutineExecutionDraftActionsInput
    {
        $validated = $this->validated();

        return new UpdateRoutineExecutionDraftActionsInput(
            new AccountIdentifier($accountIdentifier),
            new RoutineIdentifier($validated['routine_identifier']),
            new RoutineActionIdentifier($validated['routine_action_identifier']),
            $this->boolean('is_executed'),
        );
    }
}
