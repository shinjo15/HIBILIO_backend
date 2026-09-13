<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Query\GetMyBlocks\GetMyBlocksInput;

final class GetMyBlocksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $accountIdentifier): GetMyBlocksInput
    {
        return new GetMyBlocksInput($accountIdentifier);
    }
}
