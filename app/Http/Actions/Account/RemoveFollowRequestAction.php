<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\RemoveFollowRequestRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Account\Application\Usecase\Command\RemoveFollowRequest\RemoveFollowRequestInterface;
use Src\Account\Domain\Exception\FollowRequestCannotBeRemovedException;
use Src\Account\Domain\Exception\FollowRequestNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class RemoveFollowRequestAction
{
    public function __construct(private RemoveFollowRequestInterface $removeFollowRequest, private AuthServiceInterface $authService) {}

    public function __invoke(RemoveFollowRequestRequest $request): Response
    {
        try {
            $this->removeFollowRequest->execute($request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (FollowRequestCannotBeRemovedException) {
            return new Response('', 409);
        } catch (FollowRequestNotFoundException) {
            return new Response('', 404);
        }
    }
}
