<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class SupportModel extends Model
{
    protected $table = 'supports';

    public $incrementing = false;

    protected $fillable = ['account_identifier', 'post_identifier'];
}
