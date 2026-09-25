<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Hr\Models\UserBranch;
use App\Modules\Platform\Models\Tenant;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'status',
        'designation',
        'team_name',
        'hierarchy_parent_id',
        'dob',
        'phone',
        'national_id',
        'joined_at',
        'is_remote',
        'blood_group',
        'avatar',
        'device_id',
        'lefted_at',
        'left_reason',
        'departure_reason_id',
        'offboarding_new_manager_id',
        'address',
        'city',
        'zip_code',
        'celebrate_birthday',
        'justify_work_hours',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
            'joined_at' => 'date',
            'lefted_at' => 'datetime',
            'is_remote' => 'boolean',
            'celebrate_birthday' => 'boolean',
            'justify_work_hours' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function branches()
    {
        return $this->hasMany(UserBranch::class);
    }

    public function hierarchyParent()
    {
        return $this->belongsTo(User::class, 'hierarchy_parent_id')->where('status', 'active');
    }

    /**
     * Employees reporting to this manager (direct children only) — active
     * only, matching SLMS360. BelongsToTenant's scope applies here too since
     * this is just a standard relation on a tenant-scoped model, so unlike
     * SLMS360's original (which had no tenant concept to leak across), a
     * cross-tenant hierarchy_parent_id simply can't happen here even by
     * mistake.
     */
    public function hierarchyChildren()
    {
        return $this->hasMany(User::class, 'hierarchy_parent_id')->where('status', 'active');
    }

    /**
     * All descendants (any depth, up to $maxLevels) in the hierarchy tree —
     * ported from SLMS360's getAllHierarchyChildrenIds/collectDescendantIds.
     */
    public function getAllHierarchyChildrenIds(int $maxLevels = 3): array
    {
        $ids = collect();

        $this->collectDescendantIds($this, $ids, $maxLevels, 1);

        return $ids->unique()->toArray();
    }

    private function collectDescendantIds(User $employee, $ids, int $maxLevels, int $currentLevel): void
    {
        if ($currentLevel > $maxLevels) {
            return;
        }

        $children = $employee->hierarchyChildren()->get();

        if ($children->isNotEmpty()) {
            $ids->push(...$children->pluck('id'));

            foreach ($children as $child) {
                $this->collectDescendantIds($child, $ids, $maxLevels, $currentLevel + 1);
            }
        }
    }
}
