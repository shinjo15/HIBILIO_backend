<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Http\FormRequest;
use Src\Shared\Application\Service\AuthServiceInterface;

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
}
