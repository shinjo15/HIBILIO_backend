<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetReceivedFollowRequestsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetReceivedFollowRequests\GetReceivedFollowRequestsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetReceivedFollowRequestsAction
{
    public function __construct(private GetReceivedFollowRequestsInterface $getReceivedFollowRequests, private AuthServiceInterface $authService) {}

    public function __invoke(GetReceivedFollowRequestsRequest $request): JsonResponse
    {
        try {
            $output = $this->getReceivedFollowRequests->execute($request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException));
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse(['follow_requests' => array_map(static fn (array $account): array => [
            'account_identifier' => $account['accountIdentifier'],
            'account_name' => $account['accountName'],
            'account_bio' => $account['accountBio'],
            'icon_image_url' => $account['iconImageUrl'],
        ], $output->followRequests())]);
    }
}
