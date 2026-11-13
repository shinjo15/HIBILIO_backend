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
        $json = $this->isJson() ? json_decode($this->getContent()) : null;

        return [
            'routine_identifier' => ['required', 'uuid'],
            'executed_routine_action_identifiers' => [
                'present', 'array', 'list',
                static function (string $attribute, mixed $value, \Closure $fail) use ($json): void {
                    if (is_object($json) && isset($json->{$attribute}) && is_object($json->{$attribute})) {
                        $fail(__('validation.array', ['attribute' => $attribute]));
                    }
                },
            ],
            'executed_routine_action_identifiers.*' => ['required', 'uuid', 'distinct:ignore_case'],
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
            array_map(static fn (string $identifier): RoutineActionIdentifier => new RoutineActionIdentifier($identifier), $validated['executed_routine_action_identifiers']),
        );
    }
}
