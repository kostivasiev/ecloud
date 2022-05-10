<?php

namespace App\Console\Commands\Health;

use App\Models\V2\Nic;
use App\Console\Commands\Command;
use Illuminate\Support\Carbon;

class FindOrphanedNics extends Command
{
    protected $signature = 'health:find-orphaned-nics {--F|force}';

    protected $description = 'Finds orphaned NIC records';

    public function handle()
    {
        $failed = false;

        $nics = Nic::query()
            ->where('updated_at', '<=', Carbon::now()->addHours(-12))->get();

        foreach ($nics as $nic) {
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
