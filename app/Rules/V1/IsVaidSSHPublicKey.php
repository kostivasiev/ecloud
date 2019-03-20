<?php

namespace App\Rules\V1;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsValidSSHPublicKey
 * Validates the given value is a valid SSH public key
 * @package App\Rules\V1
 */
class IsValidSSHPublicKey implements Rule
{
    public function passes($attribute, $value)
    {
        $publicKeyFormatRegex = '^(ssh-[a-z]{3}) (?:[A-Za-z0-9+\/]{4})*(?:[A-Za-z0-9+\/]{2}==|[A-Za-z0-9+\/]{3}=)?(?:.*)$';

        return (preg_match('/' . $publicKeyFormatRegex . '/', $value) === 1);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute is not a valid SSH Public key';
    }
}
