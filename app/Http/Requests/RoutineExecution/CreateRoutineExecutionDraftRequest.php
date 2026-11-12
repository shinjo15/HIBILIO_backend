<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInput;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class CreateRoutineExecutionDraftRequest extends FormRequest
{
    public function authorize(AuthServiceInterface $authService): bool
    {
        if ($authService->accountIdentifier() === null) {
            throw new AuthenticationException;
        }

        return true;
    }

    public function rules(): array
    {
        return ['routine_identifier' => ['required', 'uuid']];
    }

    public function toInput(string $accountIdentifier): CreateRoutineExecutionDraftInput
    {
        $validated = $this->validated();

        return new CreateRoutineExecutionDraftInput(
            new AccountIdentifier($accountIdentifier),
            new RoutineIdentifier($validated['routine_identifier']),
        );
    }
}
