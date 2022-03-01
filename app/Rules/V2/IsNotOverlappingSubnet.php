<?php

namespace App\Rules\V2;

use App\Models\V2\Network;
use App\Models\V2\Router;
use Illuminate\Contracts\Validation\Rule;
use IPLib\Factory;

/**
 * Class IsNotOverlappingSubnet
 * @package App\Rules\V2
 */
class IsNotOverlappingSubnet implements Rule
{
    protected $router_id;
    protected $network_id;

    /**
     * IsNotOverlappingSubnet constructor.
     * @param string|null $networkId
     */
    public function __construct(?string $networkId = null)
    {
        $request = app('request');
        if ($request->has('router_id')) {
            $this->router_id = $request->input('router_id');
        }
        if (!empty($networkId)) {
            $this->network_id = $networkId;
            $this->router_id = Network::findOrFail($networkId)->router_id;
        }
    }

    public function passes($attribute, $value)
    {
        if (empty($this->router_id)) {
            return false;
        }
        $submittedRange = Factory::rangeFromString($value);
        $networks = Router::find($this->router_id)->networks;
        foreach ($networks as $network) {
            if (!empty($this->network_id) && $network->id == $this->network_id) {
                continue;
            }
            $storedRange = Factory::rangeFromString($network->subnet);
            if ($submittedRange->containsRange($storedRange) || $storedRange->containsRange($submittedRange)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must not overlap an existing CIDR range';
    }
}
