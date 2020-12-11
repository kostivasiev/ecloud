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
    protected ?string $networkId;

    /**
     * IsNotOverlappingSubnet constructor.
     * @param string|null $existingId
     */
    public function __construct(?string $existingId = null)
    {
        $this->networkId = $existingId;
    }

    public function passes($attribute, $value)
    {
        $submittedRange = Factory::rangeFromString($value);
        $router_id = app('request')->input('router_id') ?? Network::findOrFail($this->networkId)->router_id;
        $networks = Router::find($router_id)->networks;
        foreach ($networks as $network) {
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
