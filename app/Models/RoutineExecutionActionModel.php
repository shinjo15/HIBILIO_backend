<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RoutineExecutionActionModel extends Model
{
    protected $table = 'routine_execution_actions';

    public $incrementing = false;

    protected $fillable = [
        'routine_execution_identifier',
        'routine_action_identifier',
    ];
}
