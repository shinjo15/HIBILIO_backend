<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\CreateRoutineRequest;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;
use Src\Routine\Application\UseCase\CreateRoutine\CreateRoutineInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

use function report;

final readonly class CreateRoutineAction
{
    public function __construct(
        private CreateRoutineInterface $createRoutine,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(CreateRoutineRequest $request): Response
    {
        try {
            $this->createRoutine->execute(
                $request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException),
            );

            return new Response('', 201);
        } catch (Throwable $exception) {
            if ($this->shouldReport($exception)) {
                report($exception);
            }

            return new Response('', $this->statusCodeFor($exception));
        }
    }

    private function statusCodeFor(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof RuntimeException => 401,
            $exception instanceof InvalidArgumentException => 422,
            default => 500,
        };
    }

    private function shouldReport(Throwable $exception): bool
    {
        return $this->statusCodeFor($exception) === 500;
    }
}
