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

        return (($this->isIPv4Subnet($value) && $this->isIPv4Subnet($this->otherIpValue)) ||
            ($this->isIPv6Subnet($value) && $this->isIPv6Subnet($this->otherIpValue)) ||
            ($this->isIPv4($value) && $this->isIPv4($this->otherIpValue)) ||
            ($this->isIPv6($value) && $this->isIPv6($this->otherIpValue))
        );
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
