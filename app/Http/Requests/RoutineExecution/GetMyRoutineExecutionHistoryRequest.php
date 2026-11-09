<?php

declare(strict_types=1);

namespace App\Http\Requests\RoutineExecution;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInput;

final class GetMyRoutineExecutionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $dateRule = static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! preg_match('/^(?!0000)\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/D', $value)) {
                $fail('The '.$attribute.' must be an ISO 8601 date-time with an offset.');

                return;
            }
            $date = DateTimeImmutable::createFromFormat(str_contains($value, '.') ? '!Y-m-d\TH:i:s.uP' : '!Y-m-d\TH:i:sP', $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date === false || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))) {
                $fail('The '.$attribute.' must be a valid date-time.');

                return;
            }
            $utcYear = (int) $date->setTimezone(new DateTimeZone('UTC'))->format('Y');
            if ($utcYear < 1 || $utcYear > 9999) {
                $fail('The '.$attribute.' must be within the supported UTC date-time range.');
            }
        };

        return [
            'start' => ['required', $dateRule],
            'end' => ['required', $dateRule, function (string $attribute, mixed $value, Closure $fail): void {
                try {
                    if (is_string($value) && is_string($this->input('start')) && new DateTimeImmutable($value) <= new DateTimeImmutable($this->input('start'))) {
                        $fail('The end must be after the start.');
                    }
                } catch (\Exception) {
                    // The format rule reports invalid dates.
                }
            }],
        ];
    }

    public function toInput(string $accountIdentifier): GetAccountRoutineExecutionsInput
    {
        $utc = new DateTimeZone('UTC');
        $normalize = static fn (string $value): string => rtrim(rtrim((new DateTimeImmutable($value))->setTimezone($utc)->format('Y-m-d H:i:s.u'), '0'), '.');

        return new GetAccountRoutineExecutionsInput($accountIdentifier, 1, 40, $accountIdentifier, $normalize($this->string('start')->toString()), $normalize($this->string('end')->toString()));
    }
}
