<?php

namespace App\Console\Commands\Health;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FindOrphanedNics extends Command
{
    protected $signature = 'health:find-orphaned-nics';

    protected $description = 'Finds orphaned NIC records';

    public function handle()
    {
        $failed = false;
        foreach (Nic::all() as $nic) {
            if (!empty($nic->instance_id) && !$nic->instance()->exists()) {
                $this->error("Orphaned NIC {$nic->id} exists for deleted instance {$nic->instance_id}");
                $failed = true;
            }
        }

        if ($failed) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
