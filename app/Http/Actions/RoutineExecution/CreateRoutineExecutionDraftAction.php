<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\CreateRoutineExecutionDraftRequest;
use Illuminate\Http\Response;
use Src\Routine\Domain\Exception\RoutineNotFoundException;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftAlreadyExistsException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateRoutineExecutionDraftAction
{
    public function __construct(private CreateRoutineExecutionDraftInterface $useCase, private AuthServiceInterface $authService) {}

    public function __invoke(CreateRoutineExecutionDraftRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->useCase->execute($request->toInput($accountIdentifier));
        } catch (RoutineNotFoundException) {
            return new Response('', 404);
        } catch (RoutineExecutionDraftAlreadyExistsException) {
            return new Response('', 409);
        } catch (\DomainException|\InvalidArgumentException) {
            return new Response('', 422);
        }

        return new Response('', 201);
    }
}
