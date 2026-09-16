<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

use App\Http\Requests\Routine\SearchRoutinesRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
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
        try {
            $accountIdentifier = $this->authService->accountIdentifier();
        } catch (RuntimeException) {
            $accountIdentifier = null;
        }

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
            ], $output->items()),
            'total' => $output->total(),
        ]);
    }
}
