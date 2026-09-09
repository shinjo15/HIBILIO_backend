<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInput;

final class GetAccountDetailsRequest extends FormRequest
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

    public function toInput(): GetAccountDetailsInput
    {
        return new GetAccountDetailsInput(
            accountIdentifier: (string) $this->route('account_identifier'),
        );
    }
}
