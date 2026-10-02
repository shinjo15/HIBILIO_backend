<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ContactModel extends Model
{
    protected $table = 'contacts';

    protected $primaryKey = 'contact_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['contact_identifier', 'account_identifier', 'title', 'content', 'status', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'immutable_datetime'];
    }
}
