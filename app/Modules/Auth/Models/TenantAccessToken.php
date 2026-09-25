<?php

namespace App\Modules\Auth\Models;

use App\Modules\Tenancy\Support\TenantContext;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sanctum's token, made tenant-aware. Sanctum finds the token (this table
 * isn't under RLS) and then loads its user from `users` (which is). Without
 * a tenant context that user is invisible, so every real bearer-token
 * request was rejected. findToken() therefore switches into the token's own
 * tenant first. ResolveTenantFromUser then sets the same tenant from the
 * user, and clears it when the request ends.
 */
class TenantAccessToken extends PersonalAccessToken
{
    protected $table = 'personal_access_tokens';

    protected static function booted(): void
    {
        static::creating(function (self $token) {
            if ($token->tenant_id === null && app(TenantContext::class)->check()) {
                $token->tenant_id = app(TenantContext::class)->id();
            }
        });
    }

    public static function findToken($token)
    {
        $found = parent::findToken($token);

        if ($found?->tenant_id !== null) {
            app(TenantContext::class)->set($found->tenant_id);
        }

        return $found;
    }
}
