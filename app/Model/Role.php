<?php

declare(strict_types=1);

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Role extends Model
{
    /** Роль администратора: даёт доступ к служебным разделам /admin/*. */
    public const ADMIN = 'admin';

    protected $fillable = ['title'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'roles_to_users');
    }
}
