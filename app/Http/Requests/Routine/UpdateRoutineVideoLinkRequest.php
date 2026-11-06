<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use App\Http\Middleware\PreserveRoutineVideoUrl;
use App\Rules\ValidRoutineVideoUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Routine\Application\UseCase\UpdateRoutineVideoLink\UpdateRoutineVideoLinkInput;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineActionIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final class UpdateRoutineVideoLinkRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge($this->attributes->get(PreserveRoutineVideoUrl::ATTRIBUTE, []));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['video_url' => ['bail', 'sometimes', Rule::requiredIf(is_string($this->input('video_url')) && trim($this->input('video_url')) === ''), 'nullable', 'string', new ValidRoutineVideoUrl]];
    }

    public function toInput(string $accountIdentifier): UpdateRoutineVideoLinkInput
    {
        $validated = $this->validated();
        $actionIdentifier = $this->route('routine_action_identifier');

        return new UpdateRoutineVideoLinkInput(
            new RoutineIdentifier($this->route('routine_identifier')),
            new AccountIdentifier($accountIdentifier),
            $actionIdentifier === null ? null : new RoutineActionIdentifier($actionIdentifier),
            array_key_exists('video_url', $validated),
            isset($validated['video_url']) ? new RoutineVideoUrl($validated['video_url']) : null,
        );
    }
}
