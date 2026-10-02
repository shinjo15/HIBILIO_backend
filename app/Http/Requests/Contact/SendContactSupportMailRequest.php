<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Src\Contact\Application\UseCase\SendContactSupportMail\SendContactSupportMailInput;
use Src\Contact\Domain\ValueObject\ContactContent;
use Src\Contact\Domain\ValueObject\ContactTitle;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class SendContactSupportMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:50', 'not_regex:/^\s*$/u', 'not_regex:/[\r\n\x{2028}\x{2029}]/u'],
            'content' => ['required', 'string', 'max:500', 'not_regex:/^\s*$/u'],
        ];
    }

    public function toInput(string $accountIdentifier): SendContactSupportMailInput
    {
        $validated = $this->validated();

        return new SendContactSupportMailInput(new AccountIdentifier($accountIdentifier), new ContactTitle($validated['title']), new ContactContent($validated['content']));
    }
}
