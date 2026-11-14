<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use Illuminate\Http\Response;
use Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft\DeleteRoutineExecutionDraftInput;
use Src\RoutineExecution\Application\UseCase\DeleteRoutineExecutionDraft\DeleteRoutineExecutionDraftInterface;
use Src\RoutineExecution\Domain\Exception\RoutineExecutionDraftNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;
use Src\Shared\Domain\ValueObject\Identifier\RoutineIdentifier;

final readonly class DeleteRoutineExecutionDraftAction
{
    public function __construct(private DeleteRoutineExecutionDraftInterface $useCase, private AuthServiceInterface $authService) {}

    public function __invoke(string $routine_identifier): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->useCase->execute(new DeleteRoutineExecutionDraftInput(new AccountIdentifier($accountIdentifier), new RoutineIdentifier($routine_identifier)));
        } catch (RoutineExecutionDraftNotFoundException) {
            return new Response('', 404);
        } catch (\InvalidArgumentException) {
            return new Response('', 422);
        }

        return new Response('', 204);
    }
}
