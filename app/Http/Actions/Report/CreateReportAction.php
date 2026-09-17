<?php

declare(strict_types=1);

namespace App\Http\Actions\Report;

use App\Http\Requests\Report\CreateReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Src\Report\Application\UseCase\CreateReport\CreateReportInterface;
use Src\Report\Domain\Exception\DuplicateReportException;
use Src\Report\Domain\Exception\SelfReportException;
use Src\Report\Domain\Exception\TargetAccountNotFoundException;
use Src\Report\Domain\Exception\TargetPostMismatchException;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class CreateReportAction
{
    public function __construct(private CreateReportInterface $createReport, private AuthServiceInterface $authService) {}

    public function __invoke(CreateReportRequest $request): Response|JsonResponse
    {
        try {
            $this->createReport->execute($request->toInput($this->authService->accountIdentifier() ?? throw new RuntimeException));

            return new Response('', 204);
        } catch (RuntimeException) {
            return new Response('', 401);
        } catch (TargetAccountNotFoundException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 404);
        } catch (TargetPostMismatchException|SelfReportException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        } catch (DuplicateReportException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 409);
        }
    }
}
