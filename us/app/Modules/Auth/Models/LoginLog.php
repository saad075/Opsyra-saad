<?php

namespace App\Modules\Auth\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'ip_address', 'user_agent', 'device_name', 'login_at'];

    protected function casts(): array
    {
        return ['login_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
