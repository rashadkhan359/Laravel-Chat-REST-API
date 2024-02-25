<?php

namespace App\Filters\v1;

use App\Filters\ApiFilter;
use Illuminate\Http\Request;

class UserFilter extends ApiFilter{

    protected $allowedFilters = [
        'id' => ['eq', 'ne', 'gt'],
        'name' => ['eq', 'lk'], //Only equality operator is allowed
        'email' => ['eq', 'lk']
    ];

    public function transform(Request $request){
        $eloQuery = [];

        foreach($this->allowedFilters as $filter => $operators){
            $query = $request->query($filter);

            if(!isset($query)){
                continue;
            }

            $column = $this->columnMap[$filter] ?? $filter;  //if no mapping exists, just use the filter key for column

            foreach($operators as $operator){
                if(isset($query[$operator])){
                    if($operator == 'lk'){
                        $eloQuery[] = [$column, $this->operatorMap[$operator], "%{$query[$operator]}%"];
                    }else{
                        $eloQuery[] = [$column, $this->operatorMap[$operator], $query[$operator]];
                    }
                }
            }
        }

        return $eloQuery;
    }
}
