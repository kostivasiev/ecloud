<?php

namespace App\Rules\V2\FirewallRulePort;

class UniquePortRule extends BasePortRule
{
    public function passes($attribute, $value)
    {
        $altAttribute = ($attribute == 'source') ? 'destination' : 'source';
        $where = [
            [$this->parentKeyColumn, '=', $this->parentId],
            ['protocol', '=', $this->protocol],
        ];
        if ($value != 'ANY') {
            $where[] = [$attribute, '=', $value];
        }
        if ($this->{$altAttribute} != 'ANY') {
            $where[] = [$altAttribute, '=', $this->{$altAttribute}];
        }
        return $this->model->where($where)->count() == 0;
    }

    public function message()
    {
        return 'This port configuration already exists';
    }
}
