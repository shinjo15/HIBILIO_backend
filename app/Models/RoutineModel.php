<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class RoutineModel extends Model
{
    protected $table = 'routines';

    protected $primaryKey = 'routine_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'routine_identifier',
        'parent_routine_identifier',
        'routine_name',
        'routine_memo',
        'video_url',
        'account_identifier',
        'routine_execution_minutes',
        'available',
    ];

    protected function casts(): array
    {
        return [
            'available' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_identifier', 'account_identifier');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            TagModel::class,
            'routine_tags',
            'routine_identifier',
            'tag_identifier',
            'routine_identifier',
            'tag_identifier',
        )->withPivot('available')->withTimestamps();
    }
}
