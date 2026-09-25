<?php

namespace App\Modules\Platform\Models;

use Illuminate\Database\Eloquent\Model;

class TaxTable extends Model
{
    protected $fillable = [
        'country',
        'tax_year',
        'jurisdiction',
        'payload',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }
}
