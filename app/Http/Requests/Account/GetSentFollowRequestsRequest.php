<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsInput;

final class GetSentFollowRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $accountIdentifier): GetSentFollowRequestsInput
    {
        return new GetSentFollowRequestsInput($accountIdentifier);
    }
}
