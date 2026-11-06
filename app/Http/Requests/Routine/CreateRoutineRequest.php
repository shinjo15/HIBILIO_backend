<?php

declare(strict_types=1);

namespace App\Http\Requests\Routine;

use App\Http\Middleware\PreserveRoutineVideoUrl;
use App\Rules\ValidRoutineVideoUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutineActionInput;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutineInput;
use Src\Routine\Domain\ValueObject\RoutineActionMemo;
use Src\Routine\Domain\ValueObject\RoutineActionMinutes;
use Src\Routine\Domain\ValueObject\RoutineActionName;
use Src\Routine\Domain\ValueObject\RoutineExecutionMinutes;
use Src\Routine\Domain\ValueObject\RoutineMemo;
use Src\Routine\Domain\ValueObject\RoutineName;
use Src\Routine\Domain\ValueObject\RoutineTagIdentifiers;
use Src\Routine\Domain\ValueObject\RoutineVideoUrl;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\TagIdentifier;

final class CreateRoutineRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge($this->attributes->get(PreserveRoutineVideoUrl::ATTRIBUTE, []));
        $actions = $this->input('routine_actions');
        if (is_array($actions)) {
            foreach ($this->attributes->get('routine_action_video_urls_original', []) as $index => $url) {
                if (isset($actions[$index]) && is_array($actions[$index])) {
                    $actions[$index]['video_url'] = $url;
                }
            }
            $this->merge(['routine_actions' => $actions]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $rules = [
            'routine_name' => ['required', 'string', 'not_regex:/^\s*$/', 'max:50'],
            'parent_routine_identifier' => ['nullable', 'uuid'],
            'routine_memo' => ['nullable', 'string', 'not_regex:/^\s*$/', 'max:300'],
            'video_url' => ['bail', Rule::requiredIf(is_string($this->input('video_url')) && trim($this->input('video_url')) === ''), 'nullable', 'string', new ValidRoutineVideoUrl],
            'routine_execution_minutes' => ['nullable', 'integer', 'min:1'],
            'tag_identifiers' => ['sometimes', 'array'],
            'tag_identifiers.*' => ['required', 'uuid'],
            'routine_actions' => ['required', 'array', 'min:1'],
            'routine_actions.*.routine_action_name' => ['required', 'string', 'not_regex:/^\s*$/', 'max:50'],
            'routine_actions.*.routine_action_memo' => ['nullable', 'string', 'not_regex:/^\s*$/', 'max:300'],
            'routine_actions.*.routine_action_minutes' => ['nullable', 'integer', 'min:1'],
            'routine_actions.*.parent_routine_action_index' => ['nullable', 'integer', 'min:0'],
        ];
        $actions = $this->input('routine_actions');
        foreach (is_array($actions) ? $actions : [] as $index => $action) {
            if (! is_array($action)) {
                continue;
            }
            $url = $action['video_url'] ?? null;
            $rules["routine_actions.$index.video_url"] = ['bail', Rule::requiredIf(is_string($url) && trim($url) === ''), 'nullable', 'string', new ValidRoutineVideoUrl];
        }

        return $rules;
    }

    public function toInput(string $accountIdentifier): CreateRoutineInput
    {
        /**
         * @var array{
         *     routine_name: string,
         *     parent_routine_identifier?: string|null,
         *     routine_memo?: string|null,
         *     routine_execution_minutes?: int,
         *     tag_identifiers?: list<string>,
         *     routine_actions: list<array{
         *         routine_action_name: string,
         *         routine_action_memo?: string|null,
         *         routine_action_minutes?: int,
         *         parent_routine_action_index?: int|null
         *     }>
         * } $validated
         */
        $validated = $this->validated();

        return new CreateRoutineInput(
            accountIdentifier: new AccountIdentifier($accountIdentifier),
            parentRoutineIdentifier: isset($validated['parent_routine_identifier'])
                ? new RoutineIdentifier($validated['parent_routine_identifier'])
                : null,
            routineName: new RoutineName($validated['routine_name']),
            routineActions: array_map(
                static fn (array $routineAction): CreateRoutineActionInput => new CreateRoutineActionInput(
                    routineActionName: new RoutineActionName($routineAction['routine_action_name']),
                    routineActionMemo: isset($routineAction['routine_action_memo'])
                        ? new RoutineActionMemo($routineAction['routine_action_memo'])
                        : null,
                    routineActionMinutes: isset($routineAction['routine_action_minutes'])
                        ? new RoutineActionMinutes($routineAction['routine_action_minutes'])
                        : null,
                    parentRoutineActionIndex: $routineAction['parent_routine_action_index'] ?? null,
                    videoUrl: isset($routineAction['video_url']) ? new RoutineVideoUrl($routineAction['video_url']) : null,
                ),
                array_values($validated['routine_actions']),
            ),
            routineMemo: isset($validated['routine_memo']) ? new RoutineMemo($validated['routine_memo']) : null,
            routineExecutionMinutes: isset($validated['routine_execution_minutes'])
                ? new RoutineExecutionMinutes($validated['routine_execution_minutes'])
                : null,
            routineTagIdentifiers: $this->routineTagIdentifiers($validated['tag_identifiers'] ?? []),
            videoUrl: isset($validated['video_url']) ? new RoutineVideoUrl($validated['video_url']) : null,
        );
    }

    /** @param list<string> $tagIdentifiers */
    private function routineTagIdentifiers(array $tagIdentifiers): ?RoutineTagIdentifiers
    {
        if ($tagIdentifiers === []) {
            return null;
        }

        return new RoutineTagIdentifiers(array_map(
            static fn (string $tagIdentifier): TagIdentifier => new TagIdentifier($tagIdentifier),
            $tagIdentifiers,
        ));
    }
}
