<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class FollowRequestModel extends Model
{
    protected $table = 'follow_requests';

    protected $fillable = ['requesting_account_identifier', 'target_account_identifier', 'status'];
}
