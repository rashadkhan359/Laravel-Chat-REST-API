<?php

namespace App\Filters;

use Illuminate\Http\Request;

class ApiFilter{
    protected $allowedFilters = [];

    // use this to map keys that are sent/received to actual table column keys
    protected $columnMap = [];

    protected $operatorMap = [
        'eq' => '=',
        'lt' => '<',
        'lte' => '<=',
        'gt' => '>',
        'gte' => '>=',
        'ne' => '!=',
        'lk' => 'like',
    ];
}
