<?php

declare(strict_types=1);

namespace Src\Authentication\Application\Usecase\Command\StartSocialLogin;

use Src\Authentication\Application\Service\SocialLoginServiceInterface;

final readonly class StartSocialLogin implements StartSocialLoginInterface
{
    public function __construct(private SocialLoginServiceInterface $socialLoginService) {}

    public function execute(StartSocialLoginInputPort $input): StartSocialLoginOutputPort
    {
        return new StartSocialLoginOutput($this->socialLoginService->start(
            $input->provider(),
            $input->accountName(),
            $input->browserSessionIdentifier(),
        ));
    }
}
