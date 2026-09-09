<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetAccountRoutineExecutionsRequest;
use Illuminate\Http\JsonResponse;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;

final readonly class GetAccountRoutineExecutionsAction
{
    public function __construct(private GetAccountRoutineExecutionsInterface $query) {}

    public function __invoke(GetAccountRoutineExecutionsRequest $request): JsonResponse
    {
        $result = $this->query->execute($request->toInput());

        return new JsonResponse(['items' => $result->items(), 'total' => $result->total()]);
    }
}
