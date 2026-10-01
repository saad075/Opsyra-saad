<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per user, globally — not tenant-scoped, not RLS-protected. See
 * the create_user_emails_table migration for why this table exists
 * alongside the real unique(email) constraint on `users`.
 */
class UserEmail extends Model
{
    protected $table = 'user_emails';

    protected $fillable = [
        'email',
        'user_id',
    ];
}
