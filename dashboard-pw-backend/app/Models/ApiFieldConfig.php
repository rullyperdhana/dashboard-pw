<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiFieldConfig extends Model
{
    protected $fillable = [
        'endpoint',
        'field_key',
        'native_key',
        'field_label',
        'source_table',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];
}
