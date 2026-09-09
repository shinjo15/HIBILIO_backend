<?php

declare(strict_types=1);

namespace App\Http\Actions\Account;

use App\Http\Requests\Account\GetAccountDetailsRequest;
use Illuminate\Http\JsonResponse;
use Src\Account\Application\Usecase\Query\GetAccountDetails\GetAccountDetailsInterface;

final readonly class GetAccountDetailsAction
{
    public function __construct(
        private GetAccountDetailsInterface $getAccountDetails,
    ) {}

    public function __invoke(GetAccountDetailsRequest $request): JsonResponse
    {
        $accountDetails = $this->getAccountDetails->execute($request->toInput())->accountDetails();

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
