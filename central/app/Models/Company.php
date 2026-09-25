<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    /** Statuses in which the company's staff can reach their sign-in page. */
    public const REACHABLE = ['trial', 'active', 'past_due'];

    protected $fillable = [
        'client_code', 'name', 'country', 'status', 'plan_id', 'trial_ends_at',
        'admin_name', 'admin_email', 'phone', 'expected_employees',
        'active_employees', 'usage_reported_at', 'provision_error', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'usage_reported_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /** The country deployment's app, where this company's staff sign in. */
    public function appUrl(): ?string
    {
        return config("opsyra.countries.{$this->country}.url");
    }

    public function countryName(): string
    {
        return config("opsyra.countries.{$this->country}.name", strtoupper($this->country));
    }
}
