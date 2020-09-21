<?php

namespace App\Traits\V2;

use App\Models\V2\Credential;

trait Credentials
{
    public function credentials()
    {
        return $this->hasMany(Credential::class, 'resource_id', 'id');
    }
}
