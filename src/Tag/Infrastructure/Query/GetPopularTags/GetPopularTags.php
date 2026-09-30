<?php

declare(strict_types=1);

namespace Src\Tag\Infrastructure\Query\GetPopularTags;

use Illuminate\Support\Facades\DB;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsInputPort;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsInterface;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsOutput;
use Src\Tag\Application\Usecase\Query\GetPopularTags\GetPopularTagsOutputPort;

final class GetPopularTags implements GetPopularTagsInterface
{
    public function execute(GetPopularTagsInputPort $input): GetPopularTagsOutputPort
    {
        $tags = DB::table('tags')
            ->leftJoin('routine_tags', static function ($join): void {
                $join->on('tags.tag_identifier', '=', 'routine_tags.tag_identifier')
                    ->where('routine_tags.available', true);
            })
            ->leftJoin('routines', static function ($join): void {
                $join->on('routine_tags.routine_identifier', '=', 'routines.routine_identifier')
                    ->where('routines.available', true);
            })
            ->leftJoin('accounts', static function ($join): void {
                $join->on('routines.account_identifier', '=', 'accounts.account_identifier')
                    ->where('accounts.available', true)
                    ->where('accounts.status', 'active')
                    ->where('accounts.visibility', 'public');
            })
            ->where('tags.available', true)
            ->groupBy('tags.tag_identifier', 'tags.tag_name')
            ->orderByDesc('routine_count')
            ->orderBy('tags.tag_name')
            ->orderBy('tags.tag_identifier')
            ->get([
                'tags.tag_identifier',
                'tags.tag_name',
                DB::raw('count(distinct case when accounts.account_identifier is not null then routines.routine_identifier end) as routine_count'),
            ])
            ->map(static fn (object $tag): array => [
                'tagIdentifier' => (string) $tag->tag_identifier,
                'tagName' => (string) $tag->tag_name,
                'routineCount' => (int) $tag->routine_count,
            ])
            ->all();

        return new GetPopularTagsOutput($tags);
    }
}
