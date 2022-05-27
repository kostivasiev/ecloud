<?php

namespace App\Rules\V2\FirewallRulePort;

class UniquePortRule extends BasePortRule
{
    public function passes($attribute, $value)
    {
        $altAttribute = ($attribute == 'source') ? 'destination' : 'source';

        return $this->model
            ->where([
                [$this->parentKeyColumn, '=', $this->parentId],
                ['protocol', '=', $this->protocol],
                [$attribute, '=', $value],
                [$altAttribute, '=', $this->{$altAttribute}],
            ])->count() == 0;
    }

    public function message()
    {
        return 'This port configuration already exists';
    }
}
