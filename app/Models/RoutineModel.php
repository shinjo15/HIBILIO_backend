<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_identifier', 'account_identifier');
    }

    public function parentRoutine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_routine_identifier', 'routine_identifier');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(RoutineActionModel::class, 'routine_identifier', 'routine_identifier');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(PostModel::class, 'routine_identifier', 'routine_identifier');
    }

    public function customizations(): HasMany
    {
        return $this->hasMany(self::class, 'parent_routine_identifier', 'routine_identifier');
    }
}
