<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ListeningContentModel extends Model
{
    protected $table = 'listening_contents';

    protected $primaryKey = 'listening_content_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['listening_content_identifier', 'account_identifier', 'url'];
}
