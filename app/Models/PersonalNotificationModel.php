<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class PersonalNotificationModel extends Model
{
    protected $table = 'personal_notifications';

    protected $primaryKey = 'notification_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'notification_identifier',
        'receiver_account_identifier',
        'content',
        'status',
    ];
}
