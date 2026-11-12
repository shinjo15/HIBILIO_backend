<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\CreateRoutineExecutionDraftRequest;
use Illuminate\Http\Response;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\AccountAuthenticationException;
use Src\RoutineExecution\Application\UseCase\CreateRoutineExecutionDraft\CreateRoutineExecutionDraftInterface;
use Src\RoutineExecution\Domain\Exception\RoutineNotExecutableException;
use Src\RoutineExecution\Domain\Exception\RoutineNotFoundForDraftException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateRoutineExecutionDraftAction
{
    public function __construct(private CreateRoutineExecutionDraftInterface $createDraft, private AuthServiceInterface $authService) {}

    public function __invoke(CreateRoutineExecutionDraftRequest $request): Response
    {
        $account = $this->authService->accountIdentifier();
        if ($account === null) {
            return new Response('', 401);
        }
        try {
            $output = $this->createDraft->execute($request->toInput($account));
        } catch (AccountAuthenticationException) {
            return new Response('', 401);
        } catch (RoutineNotFoundForDraftException) {
            return new Response('', 404);
        } catch (RoutineNotExecutableException) {
            return new Response('', 403);
        } catch (\DomainException|\InvalidArgumentException) {
            return new Response('', 422);
        }

        return new Response('', $output->created() ? 201 : 204);
    }
}
