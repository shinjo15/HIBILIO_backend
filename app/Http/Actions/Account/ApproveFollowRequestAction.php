<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\ApproveFollowRequestRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\ApproveFollowRequest\ApproveFollowRequestInterface;
use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class ApproveFollowRequestAction
{
    public function __construct(private ApproveFollowRequestInterface $approveFollowRequest, private AuthServiceInterface $authService) {}

    public function __invoke(ApproveFollowRequestRequest $request): Response
    {
        try {
            $this->approveFollowRequest->execute($request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (FollowRequestNotFoundException) {
            return new Response('', 404);
        }
    }
}
