<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\CreateRoutineExecutionRequest;
use Illuminate\Http\Response;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecution\CreateRoutineExecutionInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateRoutineExecutionAction
{
    public function __construct(private CreateRoutineExecutionInterface $createRoutineExecution, private AuthServiceInterface $authService) {}

    public function __invoke(CreateRoutineExecutionRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->createRoutineExecution->execute($request->toInput($accountIdentifier));
        } catch (\DomainException|\InvalidArgumentException) {
            return new Response('', 422);
        }

        return new Response('', 201);
    }
}
