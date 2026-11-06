<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RoutineActionModel extends Model
{
    protected $table = 'routine_actions';

    protected $primaryKey = 'routine_action_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'routine_action_identifier',
        'parent_routine_action_identifier',
        'routine_identifier',
        'action_name',
        'action_memo',
        'action_minutes',
        'video_url',
        'available',
    ];

    protected function casts(): array
    {
        return [
            'available' => 'boolean',
        ];
    }
}
