<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RoutineExecutionModel extends Model
{
    protected $table = 'routine_executions';

    protected $primaryKey = 'routine_execution_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'routine_execution_identifier',
        'executor_account_identifier',
        'routine_identifier',
        'executed_at',
        'routine_execution_memo',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'immutable_datetime',
        ];
    }
}
