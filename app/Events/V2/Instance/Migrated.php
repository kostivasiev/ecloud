<?php

namespace App\Events\V2\Instance;

use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use Illuminate\Queue\SerializesModels;

class Migrated
{
    use SerializesModels;

    public function __construct(
        public Instance $instance,
        public HostGroup $hostGroup) {}
}
