<?php

namespace App\Rules\V2\FirewallRulePort;

use Illuminate\Support\Str;

class UniquePortRangeRule extends BasePortRule
{
    public function passes($attribute, $value)
    {
        $altAttribute = ($attribute == 'source') ? 'destination' : 'source';

        // Check subranges
        $existingPorts = $this->model
            ->where([
                [$this->parentKeyColumn, '=', $this->parentId],
                ['protocol', '=', $this->protocol],
                [$altAttribute, '=', $this->{$altAttribute}],
            ])->get();
        foreach ($existingPorts as $existingPort) {
            $parts = explode('-', $value);
            if (count($parts) > 1) {
                // if subrange match then fail
                if (Str::contains($value, '-')) {
                    $valueParts = explode('-', $value);
                    if (($parts[0] >= $valueParts[0] && $valueParts[0] <= $parts[1]) &&
                        ($parts[0] >= $valueParts[1] && $parts[1] <= $valueParts[1])
                    ) {
                        return false;
                    }
                } else {
                    if ($parts[0] >= $value && $parts[1] <= $value) {
                        return false;
                    }
                }
            }
            // if there's a 1:1 match then fail
            if (Str::contains($existingPort->{$attribute}, '-')) {
                $parts = explode('-', $existingPort->{$attribute});
                if ($value >= $parts[0] && $value <= $parts[1]) {
                    return false;
                }
            }
        }

        return true;
    }

    public function message()
    {
        return 'This port range configuration already exists';
    }
}
