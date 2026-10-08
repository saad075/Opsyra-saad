<?php

namespace App\Modules\Tenancy\Concerns;

use App\Modules\Tenancy\Models\UserEmail;

/**
 * Mirrors this model's email into the global user_emails registry on create
 * and on email change, so the app can check "does this email exist
 * anywhere" — something a plain query against this (tenant-scoped,
 * RLS-protected) table can never answer for itself, since RLS limits every
 * session to its own tenant's rows. Deletion needs no hook: user_emails.user_id
 * cascades on delete.
 *
 * Both hooks run after the real write, not before: the authoritative
 * guarantee is the unique(email) constraint on this table itself, which
 * Postgres enforces regardless of RLS — so by the time these fire, the email
 * is already known to be free, and the registry insert/update can't itself
 * conflict.
 */
trait HasGloballyUniqueEmail
{
    public static function bootHasGloballyUniqueEmail(): void
    {
        static::created(function ($model) {
            UserEmail::create(['email' => $model->email, 'user_id' => $model->id]);
        });

        static::updated(function ($model) {
            if ($model->wasChanged('email')) {
                UserEmail::where('user_id', $model->id)->update(['email' => $model->email]);
            }
        });
    }
}
