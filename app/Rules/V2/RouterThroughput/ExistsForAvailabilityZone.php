<?php

namespace App\Rules\V2\RouterThroughput;

use App\Models\V2\AvailabilityZone;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ExistsForAvailabilityZone implements Rule
{
    protected $routerThroughputs = [];

    public function __construct($availabilityZoneId)
    {
        $availabilityZone = AvailabilityZone::forUser(Auth::user())->find($availabilityZoneId);

        if (!empty($availabilityZone)) {
            $this->routerThroughputs = $availabilityZone->routerThroughputs->pluck('id')->toArray();
        }
    }

    public function passes($attribute, $value)
    {
        return in_array($value, $this->routerThroughputs);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The specified :attribute was not found';
    }
}
