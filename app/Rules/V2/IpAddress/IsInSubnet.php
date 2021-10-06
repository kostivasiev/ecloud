<?php

namespace App\Rules\V2\IpAddress;

use App\Models\V2\Network;
use Illuminate\Contracts\Validation\Rule;
use IPLib\Range\Subnet;

class IsInSubnet implements Rule
{
    private $network;

    public function __construct($networkId)
    {
        $this->network = Network::find($networkId);
    }

    public function passes($attribute, $value)
    {
        $address = \IPLib\Factory::addressFromString($value);

        if (!$this->network) {
            return false;
        }

        $subnet = Subnet::fromString($this->network ->subnet);

        return $address->matches($subnet);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is not within the network\'s subnet';
    }
}
