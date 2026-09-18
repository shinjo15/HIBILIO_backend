<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\RejectFollowRequestRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\RejectFollowRequest\RejectFollowRequestInterface;
use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RejectFollowRequestAction
{
    public function __construct(private RejectFollowRequestInterface $rejectFollowRequest, private AuthServiceInterface $authService) {}

    public function __invoke(RejectFollowRequestRequest $request): Response
    {
        try {
            $this->rejectFollowRequest->execute($request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (FollowRequestNotFoundException) {
            return new Response('', 404);
        }
    }
}
