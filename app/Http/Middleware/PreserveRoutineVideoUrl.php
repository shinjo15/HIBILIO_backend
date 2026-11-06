<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PreserveRoutineVideoUrl
{
    public const ATTRIBUTE = 'routine_video_url_original';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST') && $request->is('api/routines')) {
            $input = $request->all();
            if (array_key_exists('video_url', $input)) {
                $request->attributes->set(self::ATTRIBUTE, ['video_url' => $input['video_url']]);
            }
            if (isset($input['routine_actions']) && is_array($input['routine_actions'])) {
                $urls = [];
                foreach ($input['routine_actions'] as $index => $action) {
                    if (is_array($action) && array_key_exists('video_url', $action)) {
                        $urls[$index] = $action['video_url'];
                    }
                }
                $request->attributes->set('routine_action_video_urls_original', $urls);
            }
        }

        return $next($request);
    }
}
