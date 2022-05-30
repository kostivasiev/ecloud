<?php

namespace App\Rules\V2\FirewallRulePort;

use App\Rules\V2\ValidFirewallRulePortSourceDestination;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

class ValidPortArrayRule implements Rule
{
    public function passes($attribute, $value)
    {
        $ports = collect($value);
        foreach ($ports as $port) {
            if ($this->hasDuplicate($ports, $port)) {
                return false;
            }
            if (!($this->isValidPortSourceDestination($port['source']) ||
                $this->isValidPortSourceDestination($port['destination']))) {
                return false;
            }
            if (!$this->isUniquePortRange($ports, $port, 'source') ||
                !$this->isUniquePortRange($ports, $port, 'destination')) {
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

    private function isUniquePortRange(Collection $ports, array $item, string $property): bool
    {
        $matchCount = 0;
        $itemParts = explode('-', $item[$property]);
        if (count($itemParts) < 2) {
            return true;
        }

        foreach ($ports as $port) {
            $destinationParts = explode('-', $port['destination']);
            $sourceParts = explode('-', $port['source']);

            if ($port['protocol'] !== $item['protocol']) {
                return true;
            }

            if (count($destinationParts) > 1 && count($sourceParts) > 1) {
                if ((($sourceParts[0] >= $itemParts[0] && $itemParts[1] <= $sourceParts[1]) ||
                    ($sourceParts[0] >= $itemParts[0] && $sourceParts[1] <= $itemParts[1])) &&
                    (($destinationParts[0] >= $itemParts[0] && $itemParts[1] <= $destinationParts[1]) ||
                    ($destinationParts[0] >= $itemParts[0] && $destinationParts[1] <= $itemParts[1]))
                ) {
                    var_dump('Total Match');
                    $matchCount++;
                } elseif (($sourceParts[0] >= $itemParts[0] && $itemParts[0] <= $sourceParts[1]) &&
                    ($sourceParts[0] >= $itemParts[1] && $sourceParts[1] <= $itemParts[1])
                ) {
                    var_dump('Source Match');
                    $matchCount++;
                } elseif (($destinationParts[0] >= $itemParts[0] && $itemParts[0] <= $destinationParts[1]) &&
                    ($destinationParts[0] >= $itemParts[1] && $destinationParts[1] <= $itemParts[1])
                ) {
                    var_dump('Destination Match');
                    $matchCount++;
                }
            }
        }
        return ($matchCount > 1);
    }
}