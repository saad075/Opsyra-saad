<?php

namespace App\Modules\Platform\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** One change of company account owner (CompanyOwnership::transfer). */
class OwnershipTransfer extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $fillable = ['from_user_id', 'to_user_id', 'by_user_id', 'by_label', 'reason'];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function byUser()
    {
        return $this->belongsTo(User::class, 'by_user_id');
    }
}
