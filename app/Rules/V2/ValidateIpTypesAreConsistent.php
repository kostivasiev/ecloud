<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use IPLib\Address\IPv4;
use IPLib\Address\IPv6;
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
        return (($this->isIPv4($value) && $this->isIPv4($this->otherIpValue)) ||
            ($this->isIPv6($value) && $this->isIPv6($this->otherIpValue))
        );
    }

    public function message()
    {
        return 'The source and destination attributes must be of the same IP type IPv4/IPv6';
    }

    public function isIPv4($ipAddressRange): bool
    {
        return get_class((Subnet::parseString($ipAddressRange))->getStartAddress()) == IPv4::class;
    }

    public function isIPv6($ipAddressRange): bool
    {
        return get_class((Subnet::parseString($ipAddressRange))->getStartAddress()) == IPv6::class;
    }
}
