<?php

namespace App\Rules\V2\FirewallRulePort;

use Illuminate\Support\Str;

class PortWithinExistingRange extends BasePortRule
{
    public function passes($attribute, $value)
    {
        // check it's a port range
        if (Str::contains($value, '-')) {
            return true;
        }

        $altAttribute = ($attribute == 'source') ? 'destination' : 'source';

        $existingPorts = $this->model->where([
            [$this->parentKeyColumn, '=', $this->parentId],
            [$altAttribute, '=', $this->{$altAttribute}],
        ])->get();

        foreach ($existingPorts as $existingPort) {
            $parts = explode('-', $existingPort->{$attribute});
            if (count($parts) > 1) {
                if ($value >= $parts[0] && $value <= $parts[1]) {
                    return false;
                }
            }
        }
        return true;
    }

    public function message()
    {
        return ':attribute port exists within an existing range';
    }
}
