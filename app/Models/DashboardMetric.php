<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardMetric extends Model
{
    protected $fillable = [
        'section',
        'sort_order',
        'title',
        'color',
        'total',
        'unit',
        'default_kind',
        'trend_value',
        'trend_up',
        'data',
        'foot',
        'table_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'trend_up' => 'boolean',
            'data' => 'array',
            'foot' => 'array',
            'table_data' => 'array',
        ];
    }
}
