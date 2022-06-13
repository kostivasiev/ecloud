<?php

namespace App\Rules\V2\FirewallRulePort;

use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ValidPortArrayRule implements Rule
{
    public function passes($attribute, $value)
    {
        $ports = collect($value);
        foreach ($ports as $port) {
            if (Arr::exists($port, 'protocol') && $port['protocol'] == 'ICMPv4') {
                if ($this->hasDuplicate($ports, $port)) {
                    return false;
                }
                continue;
            }
            if (!Arr::exists($port, 'protocol') ||
                !Arr::exists($port, 'source') ||
                !Arr::exists($port, 'destination')) {
                return false;
            }
            if ($this->hasDuplicate($ports, $port)) {
                return false;
            }
            if (!($this->isValidPortSourceDestination($port['source']) ||
                $this->isValidPortSourceDestination($port['destination']))) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return ':attribute contains conflicting configurations';
    }

    private function hasDuplicate($ports, $item): bool
    {
        $matchCount = 0;
        foreach ($ports as $port) {
            if ($item['protocol'] == 'ICMPv4' && $port['protocol'] == $item['protocol']) {
                $matchCount++;
                continue;
            }
            if ($port['protocol'] == $item['protocol'] &&
                $port['source'] == $item['source'] &&
                $port['destination'] == $item['destination']) {
                $matchCount++;
            }
        }
        return ($matchCount > 1); // we expect one match
    }

    private function isValidPortSourceDestination($port): bool
    {
        $rule = new ValidFirewallRulePortSourceDestination();
        return $rule->passes('', $port);
    }
}
