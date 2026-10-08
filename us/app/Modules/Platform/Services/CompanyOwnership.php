<?php

namespace App\Modules\Platform\Services;

use App\Models\User;
use App\Modules\Hr\Models\StaffOffboardRequest;
use App\Modules\Platform\Exceptions\OwnershipRuleException;
use App\Modules\Platform\Models\OwnershipTransfer;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Rbac\Support\DefaultRoles;
use App\Modules\Tenancy\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * The company account owner: one person per company (the sign-up
 * administrator to start with) who always holds the Administrator role and
 * can't be offboarded, so a company can never lock itself out. With the
 * Administrator role itself locked (full permissions, can't be renamed or
 * deleted), there is always at least one working administrator.
 *
 * Ownership moves only by transfer: the owner hands it on, or Opsyra
 * support reassigns it through Central for a company that's locked out.
 * Every transfer is recorded in ownership_transfers.
 *
 * Idea from Frappe (the "Administrator" user can't be disabled or lose
 * System Manager) and Odoo (the admin user can't be archived), narrowed to
 * a named person the customer can hand on.
 */
class CompanyOwnership
{
    public function ownerId(int $tenantId): ?int
    {
        $id = Tenant::whereKey($tenantId)->value('owner_user_id');

        return $id === null ? null : (int) $id;
    }

    public function isOwner(User $user): bool
    {
        return $user->id === $this->ownerId($user->tenant_id);
    }

    /** Before taking the Administrator role away from someone. */
    public function ensureCanLoseAdministrator(User $user): void
    {
        if ($this->isOwner($user)) {
            throw new OwnershipRuleException("{$user->name} is the company’s account owner, so they always keep the Administrator role. The owner can hand ownership to someone else first (Company details).");
        }
    }

    /** Before offboarding someone. */
    public function ensureCanLeave(User $user): void
    {
        if ($this->isOwner($user)) {
            throw new OwnershipRuleException("{$user->name} is the company’s account owner and can’t be offboarded. They need to hand ownership to someone else first (Company details).");
        }
    }

    /**
     * Makes $to the owner. They get the Administrator role if they don't
     * have it; the previous owner keeps theirs (the new owner can change
     * it afterwards). Runs in the company's tenant context.
     *
     * @param  User|null  $by  null when Opsyra support does it ($byLabel says who)
     */
    public function transfer(Tenant $tenant, User $to, ?User $by, ?string $byLabel = null, ?string $reason = null): OwnershipTransfer
    {
        if ($to->tenant_id !== $tenant->id) {
            throw new OwnershipRuleException('That person isn’t in this company.');
        }

        if ($to->status !== 'active') {
            throw new OwnershipRuleException("{$to->name} isn’t an active member of staff, so they can’t become the account owner.");
        }

        if (StaffOffboardRequest::where('user_id', $to->id)->whereIn('status', ['pending', 'approved'])->exists()) {
            throw new OwnershipRuleException("{$to->name} is leaving the company, so they can’t become the account owner.");
        }

        return DB::transaction(function () use ($tenant, $to, $by, $byLabel, $reason) {
            $locked = Tenant::whereKey($tenant->id)->lockForUpdate()->firstOrFail();

            if ($locked->owner_user_id === $to->id) {
                throw new OwnershipRuleException("{$to->name} is already the account owner.");
            }

            return app(TenantContext::class)->runAs($tenant->id, function () use ($locked, $to, $by, $byLabel, $reason) {
                if (! $to->hasRole(DefaultRoles::ADMINISTRATOR)) {
                    $to->assignRole(DefaultRoles::ADMINISTRATOR);
                }

                $transfer = OwnershipTransfer::create([
                    'from_user_id' => $locked->owner_user_id,
                    'to_user_id' => $to->id,
                    'by_user_id' => $by?->id,
                    'by_label' => $byLabel,
                    'reason' => $reason,
                ]);

                $locked->forceFill(['owner_user_id' => $to->id])->save();

                return $transfer;
            });
        });
    }
}
