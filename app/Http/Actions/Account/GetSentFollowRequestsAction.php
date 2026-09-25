<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetSentFollowRequestsRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsInterface;
use Src\Account\Application\Usecase\Query\GetSentFollowRequests\GetSentFollowRequestsOutput;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetSentFollowRequestsAction
{
    public function __construct(private GetSentFollowRequestsInterface $getSentFollowRequests, private AuthServiceInterface $authService) {}

    public function __invoke(GetSentFollowRequestsRequest $request): JsonResponse
    {
        try {
            $output = $this->getSentFollowRequests->execute($request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException));
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse(['follow_requests' => array_map(static fn (GetSentFollowRequestsOutput $followRequest): array => [
            'account_identifier' => $followRequest->accountIdentifier(),
            'account_name' => $followRequest->accountName(),
            'account_bio' => $followRequest->accountBio(),
            'icon_image_url' => $followRequest->iconImageUrl(),
            'header_image_url' => $followRequest->headerImageUrl(),
        ], $output->followRequests())]);
    }
}
