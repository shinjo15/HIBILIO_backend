<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PostModel extends Model
{
    protected $table = 'posts';

    protected $primaryKey = 'post_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'post_identifier',
        'routine_identifier',
        'routine_execution_identifier',
        'post_category',
        'post_like_count',
        'post_support_count',
        'available',
    ];

    protected function casts(): array
    {
        return [
            'post_like_count' => 'integer',
            'post_support_count' => 'integer',
            'available' => 'boolean',
        ];
    }
}
