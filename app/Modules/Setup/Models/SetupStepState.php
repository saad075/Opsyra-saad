<?php

namespace App\Modules\Setup\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SetupStepState extends Model
{
    use BelongsToTenant;

    protected $fillable = ['step_key', 'state', 'updated_by'];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
