<?php

namespace App\Modules\Directory\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDirectoryEntry extends Model
{
    protected $table = 'client_directory';

    protected $fillable = [
        'client_code',
        'country',
        'endpoint_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
