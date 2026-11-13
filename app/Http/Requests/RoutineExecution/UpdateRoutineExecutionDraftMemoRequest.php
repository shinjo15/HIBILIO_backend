<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo\UpdateRoutineExecutionDraftMemoInput;
use Src\RoutineExecution\Domain\ValueObject\RoutineExecutionMemo;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class UpdateRoutineExecutionDraftMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'routine_identifier' => ['required', 'uuid'],
            'routine_execution_memo' => ['present', 'nullable', 'string', 'max:31'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['routine_identifier' => $this->route('routine_identifier')]);
    }

    public function toInput(string $accountIdentifier): UpdateRoutineExecutionDraftMemoInput
    {
        $validated = $this->validated();

        return new UpdateRoutineExecutionDraftMemoInput(
            new AccountIdentifier($accountIdentifier),
            new RoutineIdentifier($validated['routine_identifier']),
            $validated['routine_execution_memo'] === null ? null : new RoutineExecutionMemo($validated['routine_execution_memo']),
        );
    }
}
