<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetMyRoutineExecutionsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyRoutineExecutionsAction
{
    public function __construct(private GetAccountRoutineExecutionsInterface $query, private AuthServiceInterface $authService) {}

    public function __invoke(GetMyRoutineExecutionsRequest $request): JsonResponse
    {
        try {
            $input = $request->toInput($this->authService->accountIdentifier());
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }
        $result = $this->query->execute($input);

        return new JsonResponse(['items' => array_map(static fn (array $item): array => [
            'routine_execution_identifier' => $item['routineExecutionIdentifier'],
            'routine_identifier' => $item['routineIdentifier'],
            'routine_name' => $item['routineName'],
            'account_identifier' => $item['accountIdentifier'],
            'account_name' => $item['accountName'],
            'icon_image_url' => $item['iconImageUrl'],
            'executed_action_count' => $item['executedActionCount'],
            'posted_at' => $item['postedAt'],
            'routine_execution_memo' => $item['routineExecutionMemo'],
            'support_count' => $item['supportCount'],
            'liked' => $item['liked'],
            'supported' => $item['supported'],
        ], $result->items()), 'total' => $result->total()]);
    }
}
