<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Whether a country's prices are shown publicly. Unpublished (the default)
 * means the site keeps saying "plans coming soon" for that country.
 */
class CountryPricing extends Model
{
    protected $table = 'country_pricing';

    protected $primaryKey = 'country';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['country', 'is_published', 'published_at', 'updated_by'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'published_at' => 'datetime'];
    }

    public static function isPublished(string $country): bool
    {
        return (bool) static::whereKey($country)->value('is_published');
    }
}
