<?php

namespace App\Modules\Hr\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'department_id',
        'name',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
