<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffBankAccount extends Model
{
    use BelongsToTenant;

    private const SENSITIVE = ['account_number', 'iban', 'routing_number', 'tax_id'];

    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank_name',
        'bank_country',
        'account_number',
        'iban',
        'routing_number',
        'tax_id',
        'bank_code',
        'swift_bic',
        'account_currency',
        'updated_by',
    ];

    // Never serialised by accident — callers choose masked() or full().
    protected $hidden = self::SENSITIVE;

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'iban' => 'encrypted',
            'routing_number' => 'encrypted',
            'tax_id' => 'encrypted',
        ];
    }

    /**
     * Transfer needs something to transfer to; otherwise the payslip is paid
     * by cheque (SLMS360's rule, decided at approval).
     */
    public function canReceiveTransfer(): bool
    {
        return filled($this->account_number) || filled($this->iban);
    }

    public function full(): array
    {
        return $this->makeVisible(self::SENSITIVE)->toArray();
    }

    /**
     * What a staff member sees of their own details: enough to recognise the
     * account, not enough to reuse it.
     */
    public function masked(): array
    {
        $data = $this->toArray();

        foreach (self::SENSITIVE as $field) {
            $value = $this->{$field};
            $data[$field] = filled($value) ? str_repeat('•', 4).substr($value, -4) : null;
        }

        return $data;
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
