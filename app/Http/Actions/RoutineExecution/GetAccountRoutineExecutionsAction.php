<?php

declare(strict_types=1);

namespace App\Http\Actions\RoutineExecution;

use App\Http\Requests\RoutineExecution\GetAccountRoutineExecutionsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\RoutineExecution\Application\Usecase\Query\GetAccountRoutineExecutions\GetAccountRoutineExecutionsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Application\Service\BlockVisibilityServiceInterface;

final readonly class GetAccountRoutineExecutionsAction
{
    public function __construct(private GetAccountRoutineExecutionsInterface $query, private AuthServiceInterface $authService, private BlockVisibilityServiceInterface $blockVisibilityService) {}

    public function __invoke(GetAccountRoutineExecutionsRequest $request): JsonResponse
    {
        try {
            $viewerAccountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $viewerAccountIdentifier = null;
        }

        if (! $this->blockVisibilityService->accountIsVisible($viewerAccountIdentifier, (string) $request->route('account_identifier'))) {
            return new JsonResponse([], 404);
        }

        $result = $this->query->execute($request->toInput($viewerAccountIdentifier));

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
        ], $result->items()), 'total' => $result->total()]);
    }
}
