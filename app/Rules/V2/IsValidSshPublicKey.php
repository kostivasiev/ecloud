<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;
use phpseclib3\Crypt\PublicKeyLoader;

/**
 * Class ValidSshPublicKey
 * @package App\Rules\V2
 */
class IsValidSshPublicKey implements Rule
{

    public function passes($attribute, $value)
    {
        try {
            PublicKeyLoader::loadPublicKey($value);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute must be a valid SSH public key';
    }
}
