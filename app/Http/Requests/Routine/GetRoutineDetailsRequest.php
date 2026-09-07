<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use Illuminate\Foundation\Http\FormRequest;
use Src\Routine\Application\Usecase\Query\GetRoutineDetails\GetRoutineDetailsInput;

final class GetRoutineDetailsRequest extends FormRequest
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

    public function toInput(): GetRoutineDetailsInput
    {
        return new GetRoutineDetailsInput(
            routineIdentifier: (string) $this->route('routine_identifier'),
        );
    }
}
