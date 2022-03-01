<?php

namespace App\Traits\V1;

trait Encryption
{
    /**
     * @return \UKFast\Helpers\Encryption\Aes\Encryption
     */
    protected function encryption()
    {
        $encryption = new \UKFast\Helpers\Encryption\Aes\Encryption();
        $encryption->setKey(app('encryption_key'))
            ->setIv(config('encryption.aes_iv'));
        return $encryption;
    }
}
