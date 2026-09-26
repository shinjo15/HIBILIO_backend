<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use Illuminate\Http\Response;
use Src\Authentication\Application\Usecase\Command\Logout\LogoutInput;
use Src\Authentication\Application\Usecase\Command\Logout\LogoutInterface;

final readonly class LogoutAction
{
    public function __construct(private LogoutInterface $logout) {}

    public function __invoke(): Response
    {
        $this->logout->execute(new LogoutInput);

        return new Response('', 204);
    }
}
