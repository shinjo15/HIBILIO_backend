<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\GetReceivedFollowRequests\GetReceivedFollowRequestsInput;

final class GetReceivedFollowRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $accountIdentifier): GetReceivedFollowRequestsInput
    {
        return new GetReceivedFollowRequestsInput($accountIdentifier);
    }
}
