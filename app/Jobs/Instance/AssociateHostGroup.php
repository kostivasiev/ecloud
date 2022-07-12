<?php

namespace App\Jobs\Instance;

use App\Jobs\TaskJob;
use App\Models\V2\ResourceTier;
use App\Traits\V2\Jobs\Instance\ResolveHostGroup;

class AssociateHostGroup extends TaskJob
{
    use ResolveHostGroup;

    public function handle()
    {
        $instance = $this->task->resource;
        if ($hostGroup = $this->resolveHostGroup()) {
            $instance->hostGroup()->associate($hostGroup);
            $instance->saveQuietly();
        }
    }
}
