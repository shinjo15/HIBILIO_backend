<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\UpdateRoutineExecutionDraftActionsRequest;
use Illuminate\Http\Response;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftActions\UpdateRoutineExecutionDraftActionsInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class UpdateRoutineExecutionDraftActionsAction
{
    public function __construct(private UpdateRoutineExecutionDraftActionsInterface $useCase, private AuthServiceInterface $authService) {}

    public function __invoke(UpdateRoutineExecutionDraftActionsRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->useCase->execute($request->toInput($accountIdentifier));
        } catch (RoutineExecutionDraftNotFoundException) {
            return new Response('', 404);
        } catch (\DomainException|\InvalidArgumentException) {
            return new Response('', 422);
        }

        return new Response('', 204);
    }
}
