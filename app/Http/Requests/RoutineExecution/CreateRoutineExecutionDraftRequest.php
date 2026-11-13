<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['routine_identifier' => ['required', 'uuid']];
    }

    public function toInput(string $accountIdentifier): CreateRoutineExecutionDraftInput
    {
        return new CreateRoutineExecutionDraftInput(
            new AccountIdentifier($accountIdentifier),
            new RoutineIdentifier($this->validated()['routine_identifier']),
        );
    }
}
