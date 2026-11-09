<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use Illuminate\Http\JsonResponse;
use Src\Like\Application\Usecase\Query\GetLikeNotificationSetting\GetLikeNotificationSettingInput;
use Src\Like\Application\Usecase\Query\GetLikeNotificationSetting\GetLikeNotificationSettingInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetLikeNotificationSettingAction
{
    public function __construct(private AuthServiceInterface $auth, private GetLikeNotificationSettingInterface $query) {}

    public function __invoke(): JsonResponse
    {
        $account = $this->auth->accountIdentifier();
        if ($account === null) {
            return new JsonResponse([], 401);
        }
        $result = $this->query->execute(new GetLikeNotificationSettingInput($account));
        if ($result->enabled() === null) {
            return new JsonResponse([], 401);
        }

        return new JsonResponse(['enabled' => $result->enabled()]);
    }
}
