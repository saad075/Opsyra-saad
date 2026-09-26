<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Hr\Models\Branch;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** A public or company holiday — for every branch, or one branch. */
class Holiday extends Model
{
    use BelongsToTenant;

    protected $fillable = ['branch_id', 'date', 'name'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
