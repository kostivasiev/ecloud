<?php

namespace App\Rules\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use Illuminate\Contracts\Validation\Rule;

class IsSameNetworkAsNic implements Rule
{
    private $nic;

    public function __construct($nicId)
    {
        $this->nic = Nic::find($nicId);
    }

    public function passes($attribute, $value)
    {
        if (!$this->nic) {
            return false;
        }

        $ipAddress = IpAddress::findOrFail($value);

        return ($ipAddress->network->id == $this->nic->network_id);
    }

    public function message()
    {
        return 'The :attribute must be on the same network as the NIC';
    }
}
