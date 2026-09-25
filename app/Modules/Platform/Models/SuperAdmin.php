<?php

namespace App\Modules\Platform\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * Deliberately separate from App\Models\User / the "tenant" auth guard — see
 * config/auth.php's "super_admin" guard. A tenant user must never be able to
 * authenticate as platform staff, even if application code has a bug.
 */
class SuperAdmin extends Model implements AuthenticatableContract
{
    use Authenticatable, Notifiable;

    protected $fillable = [
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
