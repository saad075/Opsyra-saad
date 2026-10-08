<?php

namespace App\Modules\Setup\Models;

use App\Modules\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Per-tenant key/value settings — the company profile for now. Read through
 * get()/set() so callers never care how a value is stored.
 */
class TenantSetting extends Model
{
    use BelongsToTenant;

    public const COMPANY_KEYS = ['company_name', 'address', 'city', 'phone', 'email', 'timezone', 'currency'];

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function many(array $keys): array
    {
        $stored = static::whereIn('key', $keys)->pluck('value', 'key');

        return collect($keys)->mapWithKeys(fn ($key) => [$key => $stored[$key] ?? null])->all();
    }
}
