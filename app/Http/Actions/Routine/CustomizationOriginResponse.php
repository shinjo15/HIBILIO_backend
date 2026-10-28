<?php

declare(strict_types=1);

namespace App\Http\Actions\Routine;

final class CustomizationOriginResponse
{
    /** @param array{routineIdentifier: string, routineName: string, accountName: string, iconImageUrl: ?string}|null $origin */
    public static function from(?array $origin): ?array
    {
        return $origin === null ? null : [
            'routine_identifier' => $origin['routineIdentifier'],
            'routine_name' => $origin['routineName'],
            'account_name' => $origin['accountName'],
            'icon_image_url' => $origin['iconImageUrl'],
        ];
    }
}
