<?php

namespace App\Rules\V2;

use App\Models\V2\Network;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsNotOverlappingSubnet
 * @package App\Rules\V2
 */
class IsNotOverlappingSubnet implements Rule
{
    protected ?string $existingId;

    /**
     * IsNotOverlappingSubnet constructor.
     * @param string|null $existingId
     */
    public function __construct(?string $existingId = null)
    {
        $this->existingId = $existingId;
    }

    public function passes($attribute, $value)
    {
        $parts = explode("/", $value);
        $query = Network::where('subnet', 'LIKE', $parts[0].'%');
        if (!is_null($this->existingId)) {
            $query->where('id', '!=', $this->existingId);
        }
        return $query->get()->count() === 0;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must not overlap another CIDR subnet';
    }
}
