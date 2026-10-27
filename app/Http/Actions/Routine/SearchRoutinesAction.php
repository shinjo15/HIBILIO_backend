<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\SearchRoutinesRequest;
use Illuminate\Http\JsonResponse;
use Src\Routine\Application\Usecase\Query\SearchRoutines\SearchRoutinesInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class SearchRoutinesAction
{
    public function __construct(
        private SearchRoutinesInterface $searchRoutines,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(SearchRoutinesRequest $request): JsonResponse
    {
        $accountIdentifier = $this->authService->accountIdentifier();

        $output = $this->searchRoutines->execute($request->toInput($accountIdentifier));

        return new JsonResponse([
            'items' => array_map(static fn (array $item): array => [
                'item_type' => $item['itemType'],
                'routine_identifier' => $item['routineIdentifier'],
                'routine_execution_identifier' => $item['routineExecutionIdentifier'],
                'routine_name' => $item['routineName'],
                'account_identifier' => $item['accountIdentifier'],
                'account_name' => $item['accountName'],
                'icon_image_url' => $item['iconImageUrl'],
                'tags' => array_map(static fn (array $tag): array => [
                    'tag_identifier' => $tag['tagIdentifier'],
                    'tag_name' => $tag['tagName'],
                ], $item['tags']),
                'published_at' => $item['publishedAt'],
                'posted_at' => $item['postedAt'],
                'post_identifier' => $item['postIdentifier'],
                'routine_execution_minutes' => $item['routineExecutionMinutes'],
                'routine_actions' => array_map(static fn (array $action): array => [
                    'routine_action_identifier' => $action['routineActionIdentifier'],
                    'action_name' => $action['actionName'],
                    'action_minutes' => $action['actionMinutes'],
                ], $item['routineActions']),
                'execution_count' => $item['executionCount'],
                'customization_count' => $item['customizationCount'],
                ...self::postFields($item),
            ], $output->items()),
            'total' => $output->total(),
        ]);
    }

    private static function postFields(array $item): array
    {
        return $item['itemType'] === 'routine'
            ? ['post_like_count' => $item['postLikeCount'], 'liked' => $item['liked']]
            : [
                'post_like_count' => $item['postLikeCount'],
                'support_count' => $item['supportCount'],
                'supported' => $item['supported'],
                'executed_action_count' => $item['executedActionCount'],
                'routine_execution_memo' => $item['routineExecutionMemo'],
            ];
    }
}
