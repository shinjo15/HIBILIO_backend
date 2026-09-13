<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Requests\Authentication\StartSocialLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Src\Authentication\Application\Usecase\Command\StartSocialLogin\StartSocialLoginInterface;
use Throwable;

final readonly class StartSocialLoginAction
{
    public function __construct(private StartSocialLoginInterface $startSocialLogin) {}

    public function __invoke(StartSocialLoginRequest $request): RedirectResponse|Response
    {
        try {
            $output = $this->startSocialLogin->execute($request->toInput($request->session()->getId()));

            return redirect()->away($output->authorizationUrl());
        } catch (Throwable $exception) {
            report($exception);

            return new Response('', 500);
        }
    }
}
