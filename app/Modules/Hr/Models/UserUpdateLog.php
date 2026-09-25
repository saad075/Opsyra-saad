<?php

namespace App\Modules\Hr\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class UserUpdateLog extends Model
{
    use BelongsToTenant;

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'updated_by',
        'field_name',
        'old_value',
        'new_value',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
