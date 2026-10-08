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
        'synced_at', 'sync_error', 'suspended_at',
    ];

    /** Paying (or due to pay) — counted in monthly revenue. */
    public const PAYING = ['active', 'past_due'];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'usage_reported_at' => 'datetime',
            'synced_at' => 'datetime',
            'suspended_at' => 'datetime',
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

    /**
     * Staff count used for pricing: what the country last reported, or what
     * they told us at sign-up until the first report arrives.
     */
    public function billableEmployees(): int
    {
        return $this->active_employees ?? $this->expected_employees ?? 0;
    }

    /** This company's monthly price on its current plan, or null if unpriced. */
    public function monthlyPrice(): ?float
    {
        $price = $this->plan?->prices->firstWhere('country', $this->country);

        return $price ? $price->monthlyFor($this->billableEmployees()) : null;
    }

    public function currency(): string
    {
        return config("opsyra.countries.{$this->country}.currency", 'USD');
    }

    public function countryName(): string
    {
        return config("opsyra.countries.{$this->country}.name", strtoupper($this->country));
    }
}
