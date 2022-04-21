<?php

namespace App\Rules\V2\IpAddress;

use App\Models\V2\Network;
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

        $lock = Cache::lock("ip_address." . $value, 5);
        try {
            $lock->block(5);
            return $this->network->ipAddresses()->where('ip_address', $value)->count() == 0;
        } finally {
            $lock->release();
        }
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is already in use';
    }
}
