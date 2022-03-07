<?php

namespace App\Services\V1\Resource;

interface ResourceInterface
{
    /**
     * Returns an array of properties from /UKFast/Api/Resource/Property namespace
     * describing the resource properties required
     *
     * @return array
     */
    public function properties();
}
