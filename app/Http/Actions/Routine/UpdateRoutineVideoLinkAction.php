<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\UpdateRoutineVideoLinkRequest;
use Illuminate\Http\Response;
use Src\Routine\Application\UseCase\UpdateRoutineVideoLink\UpdateRoutineVideoLinkInterface;
use Src\Routine\Domain\Exception\RoutineNotFoundException;
use Src\Routine\Domain\Exception\RoutineNotOwnedException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class UpdateRoutineVideoLinkAction
{
    public function __construct(private UpdateRoutineVideoLinkInterface $update, private AuthServiceInterface $auth) {}

    public function __invoke(UpdateRoutineVideoLinkRequest $request): Response
    {
        $account = $this->auth->accountIdentifier();
        if ($account === null) {
            return new Response('', 401);
        }
        try {
            $this->update->execute($request->toInput($account));
        } catch (RoutineNotFoundException) {
            return new Response('', 404);
        } catch (RoutineNotOwnedException) {
            return new Response('', 403);
        }

        return new Response('', 204);
    }
}
