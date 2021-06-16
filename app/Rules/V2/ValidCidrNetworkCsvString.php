<?php
namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class ValidCidrNetworkCsvString implements Rule
{
    /**
     * Validate the value is a comma separated list of:
     * - CIDR subnet range (0.0.0.0/24)
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return false;
        }

        $valueArray = explode(',', $value);

        foreach ($valueArray as $valueItem) {
            if (!(new ValidCidrSubnet())->passes($attribute, $valueItem)) {
                return false;
            }
        }
        return true;
    }

    public function message()
    {
        return 'The :attribute must contain a valid comma separated list of CIDR subnets';
    }
}