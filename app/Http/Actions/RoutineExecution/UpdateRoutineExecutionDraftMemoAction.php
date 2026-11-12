<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\UpdateRoutineExecutionDraftMemoRequest;
use Illuminate\Http\Response;
use RuntimeException;
use Src\RoutineExecution\Application\UseCase\UpdateRoutineExecutionDraftMemo\UpdateRoutineExecutionDraftMemoInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class UpdateRoutineExecutionDraftMemoAction
{
    public function __construct(private UpdateRoutineExecutionDraftMemoInterface $usecase, private AuthServiceInterface $authService) {}

    public function __invoke(UpdateRoutineExecutionDraftMemoRequest $request): Response
    {
        try {
            $account = $this->authService->accountIdentifier() ?? throw new RuntimeException;
        } catch (RuntimeException) {
            return new Response('', 401);
        }

        try {
            $this->usecase->execute($request->toInput($account));
        } catch (RoutineExecutionDraftNotFoundException) {
            return new Response('', 404);
        }

        return new Response('', 204);
    }
}
