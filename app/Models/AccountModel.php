<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AccountModel extends Model
{
    protected $table = 'accounts';

    protected $primaryKey = 'account_identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['account_identifier', 'account_name', 'account_bio', 'email_address', 'available', 'status', 'ban_until', 'visibility'];

    protected function casts(): array
    {
        return ['available' => 'boolean', 'ban_until' => 'immutable_datetime'];
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(AccountSocialLinkModel::class, 'account_identifier', 'account_identifier')->orderBy('position');
    }

    public function favoriteTags(): HasMany
    {
        return $this->hasMany(FavoriteTagModel::class, 'account_identifier', 'account_identifier');
    }
}
