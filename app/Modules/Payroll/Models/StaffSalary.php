<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class StaffSalary extends Model
{
    use BelongsToTenant;

    public const FREQUENCIES = ['monthly', 'biweekly'];

    protected $fillable = [
        'user_id',
        'effective_from',
        'pay_frequency',
        'currency',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
        ];
    }

    /**
     * The salary in force for $userId on $date: the latest one that had
     * started by then.
     */
    public static function effectiveFor(int $userId, mixed $date): ?self
    {
        return static::where('user_id', $userId)
            ->whereDate('effective_from', '<=', Carbon::parse($date))
            ->orderByDesc('effective_from')
            ->first();
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function components()
    {
        return $this->hasMany(StaffSalaryComponent::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
