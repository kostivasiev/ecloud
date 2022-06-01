<?php

namespace App\Rules\V2\FirewallRulePort;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class UniquePortListRule implements Rule
{
    public string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function passes($attribute, $value)
    {
        // Check the value is a range
        if (!Str::containsAll($value, ['-', ','])) {
            return true;
        }

        // value has a comma separated list of what could be single ports, ranges or a combination
        foreach (explode(',', $value) as $item) {
            if (!((Str::contains($item, '-')) ?
                (app()->makeWith(UniquePortRangeRule::class, ['class' => $this->class]))->passes($attribute, $item) :
                (app()->makeWith(UniquePortRule::class, ['class' => $this->class]))->passes($attribute, $item))) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'There are port(s) conflicting with existing rules in this configuration';
    }
}
