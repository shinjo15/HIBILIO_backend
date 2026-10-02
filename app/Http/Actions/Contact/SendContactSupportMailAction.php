<?php

declare(strict_types=1);

namespace App\Http\Actions\Contact;

use App\Http\Requests\Contact\SendContactSupportMailRequest;
use Illuminate\Http\Response;
use Src\Contact\Application\UseCase\SendContactSupportMail\SendContactSupportMailInterface;
use Src\Contact\Domain\Exception\ContactAccountNotFoundException;
use Src\Shared\Application\Service\AuthServiceInterface;
use Throwable;

final readonly class SendContactSupportMailAction
{
    public function __construct(
        private SendContactSupportMailInterface $sendContactSupportMail,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(SendContactSupportMailRequest $request): Response
    {
        $accountIdentifier = $this->authService->accountIdentifier();
        if ($accountIdentifier === null) {
            return new Response('', 401);
        }

        try {
            $this->sendContactSupportMail->execute($request->toInput($accountIdentifier));

            return new Response('', 204);
        } catch (ContactAccountNotFoundException) {
            return new Response('', 401);
        } catch (Throwable) {
            return new Response('', 500);
        }
    }
}
