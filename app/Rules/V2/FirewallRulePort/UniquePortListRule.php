<?php

namespace App\Rules\V2\FirewallRulePort;

use Illuminate\Support\Str;

class UniquePortListRule extends BasePortRule
{
    public function passes($attribute, $value)
    {
        if (!Str::contains($value, ',')) {
            return true;
        }
        return $this->model->where(function ($query) use ($attribute, $value) {
            $query->where($this->parentKeyColumn, '=', $this->parentId);
            $query->where(function ($query) use ($attribute, $value) {
                $iteration = 0;
                foreach (explode(',', $value) as $item) {
                    if ($iteration == 0) {
                        $query->where($attribute, $item);
                        $iteration++;
                        continue;
                    }
                    $query->orWhere($attribute, $item);
                }
            });
        })->count() == 0;
    }

    public function message()
    {
        return 'There are port(s) conflicting with existing rules in this configuration';
    }
}
