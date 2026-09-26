<?php

declare(strict_types=1);

namespace App\Http\Actions\Authentication;

use App\Http\Support\PersistentLoginCookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Authentication\Application\UseCase\RevokePersistentLoginToken\RevokePersistentLoginTokenInput;
use Src\Authentication\Application\UseCase\RevokePersistentLoginToken\RevokePersistentLoginTokenInterface;
use Src\Authentication\Domain\ValueObject\PersistentLoginSelector;

final readonly class LogoutAction
{
    public function __construct(private RevokePersistentLoginTokenInterface $revokePersistentLoginToken) {}

    public function __invoke(Request $request): Response
    {
        $cookie = $request->cookie('persistent_login');
        if (is_string($cookie) && preg_match('/\A([a-f0-9]{32}):([a-f0-9]{64})\z/D', $cookie, $matches) === 1) {
            $this->revokePersistentLoginToken->execute(new RevokePersistentLoginTokenInput(new PersistentLoginSelector($matches[1]), $matches[2]));
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return (new Response('', 204))->withCookie(PersistentLoginCookie::forget());
    }
}
