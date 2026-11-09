<?php

declare(strict_types=1);

namespace App\Http\Actions\Like;

use App\Http\Requests\Like\ChangeLikeNotificationSettingRequest;
use Illuminate\Http\Response;
use Src\Like\Application\UseCase\ChangeLikeNotificationSetting\ChangeLikeNotificationSettingInput;
use Src\Like\Application\UseCase\ChangeLikeNotificationSetting\ChangeLikeNotificationSettingInterface;
use Src\Like\Domain\ValueObject\LikeNotificationEnabled;
use Src\Shared\Application\Service\AuthServiceInterface;
use Src\Shared\Domain\ValueObject\Identifier\AccountIdentifier;

final readonly class ChangeLikeNotificationSettingAction
{
    public function __construct(private AuthServiceInterface $auth, private ChangeLikeNotificationSettingInterface $change) {}

    public function __invoke(ChangeLikeNotificationSettingRequest $request): Response
    {
        $account = $this->auth->accountIdentifier();
        if ($account === null || ! $this->change->execute(new ChangeLikeNotificationSettingInput(new AccountIdentifier($account), new LikeNotificationEnabled($request->boolean('enabled'))))) {
            return new Response('', 401);
        }

        return new Response('', 204);
    }
}
