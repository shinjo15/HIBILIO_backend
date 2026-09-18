<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Src\Account\Application\Usecase\Command\ApproveFollowRequest\ApproveFollowRequestInput;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final class ApproveFollowRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function toInput(string $targetAccountIdentifier): ApproveFollowRequestInput
    {
        return new ApproveFollowRequestInput(new AccountIdentifier($targetAccountIdentifier), new AccountIdentifier((string) $this->route('requesting_account_identifier')));
    }
}
