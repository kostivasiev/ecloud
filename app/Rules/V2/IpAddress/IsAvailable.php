<?php

namespace App\Rules\V2\IpAddress;

use App\Exceptions\V2\IpAddressValidationException;
use App\Models\V2\Network;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class IsAvailable implements Rule
{
    private $network;

    public function __construct($networkId)
    {
        $this->network = Network::find($networkId);
    }

    public function passes($attribute, $value)
    {
        if (!$this->network) {
            return false;
        }

        if ($this->network->isReservedAddress($value)) {
            return false;
        }

        return $this->network->ipAddresses()->where('ip_address', $value)->count() == 0;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is already in use';
    }
}
