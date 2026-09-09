<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetMyAccountRequest;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;
use Src\Shared\Application\Service\AuthServiceInterface;

final readonly class GetMyAccountAction
{
    public function __construct(
        private GetAccountDetailsInterface $getAccountDetails,
        private AuthServiceInterface $authService,
    ) {}

    public function __invoke(GetMyAccountRequest $request): JsonResponse
    {
        try {
            $input = $request->toInput($this->authService->accountIdentifier());
        } catch (RuntimeException) {
            return new JsonResponse([], 401);
        }

        $accountDetails = $this->getAccountDetails->execute($input)->accountDetails();

        if ($accountDetails === null) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse([
            'account_identifier' => $accountDetails['accountIdentifier'],
            'account_name' => $accountDetails['name'],
            'account_bio' => $accountDetails['bio'],
            'favorite_tags' => array_map(static fn (array $tag): array => [
                'tag_identifier' => $tag['tagIdentifier'],
                'tag_name' => $tag['tagName'],
            ], $accountDetails['favoriteTags']),
            'social_links' => array_map(static fn (array $socialLink): array => [
                'social_type' => $socialLink['socialType'],
                'social_url' => $socialLink['socialUrl'],
            ], $accountDetails['socialLinks']),
        ]);
    }
}
