<?php

namespace App\Modules\Hr\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
    ];

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }
}
