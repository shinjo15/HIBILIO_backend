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
        return ['routine_execution_memo' => ['present', 'nullable', 'string', 'max:31']];
    }

    public function toInput(string $accountIdentifier): UpdateRoutineExecutionDraftMemoInput
    {
        $value = $this->validated()['routine_execution_memo'];

        return new UpdateRoutineExecutionDraftMemoInput(new AccountIdentifier($accountIdentifier), new RoutineIdentifier($this->route('routine_identifier')), $value === null ? null : new RoutineExecutionMemo($value));
    }
}
