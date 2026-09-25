<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use Illuminate\Http\Response;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class LogoutAction
{
    public function __construct(private AuthServiceInterface $authService) {}

    public function __invoke(): Response
    {
        $this->authService->logout();

        return new Response('', 204);
    }
}
