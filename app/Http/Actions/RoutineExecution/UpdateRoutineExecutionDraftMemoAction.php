<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\UpdateRoutineExecutionDraftMemoRequest;
use Illuminate\Http\Response;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo\UpdateRoutineExecutionDraftMemoInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class UpdateRoutineExecutionDraftMemoAction
{
    public function __construct(private UpdateRoutineExecutionDraftMemoInterface $useCase, private AuthServiceInterface $authService) {}

    public function __invoke(UpdateRoutineExecutionDraftMemoRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->useCase->execute($request->toInput($accountIdentifier));
        } catch (RoutineExecutionDraftNotFoundException) {
            return new Response('', 404);
        } catch (\InvalidArgumentException) {
            return new Response('', 422);
        }

        return new Response('', 204);
    }
}
