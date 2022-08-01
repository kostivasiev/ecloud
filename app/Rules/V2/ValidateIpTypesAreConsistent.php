<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
use IPLib\Factory;
use IPLib\Range\Subnet;

class ValidateIpTypesAreConsistent implements Rule
{
    public ?string $otherIpValue;

    public function __construct(?string $otherIpValue)
    {
        $this->otherIpValue = $otherIpValue;
    }

    public function passes($attribute, $value)
    {
        if ($value == 'ANY' || $this->otherIpValue == 'ANY') {
            return true;
        }
        if (empty($value) || empty($this->otherIpValue)) {
            return false;
        }

        $valueArray = explode(',', preg_replace('/\s+/', '', $value));

        $this->otherIpValue = preg_replace('/\s+/', '', $this->otherIpValue);
        $otherIpArray = explode(',', $this->otherIpValue);

        foreach ($valueArray as $valueItem) {
            if (($slashPos = strpos($valueItem, '/')) > 0) {
                $valueItem = substr($valueItem, 0, $slashPos);
            }
            $rangeItems = explode('-', $valueItem);
            foreach ($rangeItems as $rangeItem) {
                foreach ($otherIpArray as $otherIp) {
                    $otherRangeItems = explode('-', $otherIp);
                    foreach ($otherRangeItems as $otherRangeItem) {
                        if (($slashPos = strpos($otherRangeItem, '/')) > 0) {
                            $otherRangeItem = substr($otherRangeItem, 0, $slashPos);
                        }
                        if (!($this->isIPv4Subnet($rangeItem) && $this->isIPv4Subnet($otherRangeItem)) &&
                            !($this->isIPv4($rangeItem) && $this->isIPv4($otherRangeItem)) &&
                            !($this->isIPv6($rangeItem) && $this->isIPv6($otherRangeItem))
                        ) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function message()
    {
        return 'The source and destination attributes must be of the same IP type IPv4/IPv6';
    }

    public function isIPv4($ipAddress): bool
    {
        $parsed = Factory::parseAddressString($this->getFirstElementIfRange($ipAddress));
        if ($parsed === null) {
            return false;
        }
        return get_class($parsed) == IPv4::class;
    }

    public function isIPv4Subnet($ipAddressSubnet): bool
    {
        $subnet = Subnet::parseString($ipAddressSubnet);
        if ($subnet === null) {
            return false;
        }
        return get_class($subnet->getStartAddress()) == IPv4::class;
    }

    public function isIPv6($ipAddress): bool
    {
        $parsed = Factory::parseAddressString($this->getFirstElementIfRange($ipAddress));
        if ($parsed === null) {
            return false;
        }
        return get_class($parsed) == IPv6::class;
    }

    public function isIPv6Subnet($ipAddressSubnet): bool
    {
        $subnet = Subnet::parseString($ipAddressSubnet);
        if ($subnet === null) {
            return false;
        }
        return get_class($subnet->getStartAddress()) == IPv6::class;
    }

    public function getFirstElementIfRange($ipAddressRange): string
    {
        $parts = explode('-', $ipAddressRange);
        return (count($parts) > 1) ? $parts[0] : $ipAddressRange;
    }
}
