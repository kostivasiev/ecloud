<?php

namespace Tests\unit\Traits\V2;

use App\Models\V2\ResellerScopeable;

class TaskableResellerScopableTestModel extends TaskableTestModel implements ResellerScopeable
{
    public $resellerId;

    public function getResellerId(): int
    {
        return $this->resellerId;
    }
}
