<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LikeModel extends Model
{
    protected $table = 'likes';

    public $incrementing = false;

    protected $fillable = ['account_identifier', 'post_identifier'];
}
